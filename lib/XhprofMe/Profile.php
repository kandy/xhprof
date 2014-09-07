<?php
namespace XhprofMe;

/**
 * Domain object for handling profile runs.
 *
 * Provides method to manipulate the data from a single profile run.
 */
class Profile
{
    /**
     * @const Key used for methods with no parent
     */
    const NO_PARENT = '';

    protected $_data;
    protected $_collapsed;
    protected $_indexed;
    protected $_visited;

    protected $_keys = array('ct', 'wt', 'cpu', 'mu', 'pmu');
    protected $_exclusiveKeys = array('ewt', 'ecpu', 'emu', 'epmu');
    protected $_functionCount;

    public function __construct($profile, $convert = true)
    {
        $this->_data = $profile;
        if (!empty($profile['profile']) && $convert) {
            $this->_process();
        }
    }

    /**
     * Convert the raw data into a flatter list that is easier to use.
     *
     * This removes some of the parentage detail as all calls of a given
     * method are aggregated. We are not able to maintain a full tree structure
     * in any case, as xhprof only keeps one level of detail.
     *
     * @return void
     */
    protected function _process()
    {
        $result = array();
        foreach ($this->_data['profile'] as $name => $values) {
            list($parent, $func) = $this->splitName($name);

            // Generate collapsed data.
            if (isset($result[$func])) {
                $result[$func] = $this->_sumKeys($result[$func], $values);
                $result[$func]['parents'][] = $parent;
            } else {
                $result[$func] = $values;
                $result[$func]['parents'] = array($parent);
            }

            // Build the indexed data.
            if ($parent === null) {
                $parent = self::NO_PARENT;
            }
            if (!isset($this->_indexed[$parent])) {
                $this->_indexed[$parent] = array();
            }
            $this->_indexed[$parent][$func] = $values;
        }
        $this->_collapsed = $result;
    }

    /**
     * Find symbols that are the children of the given name.
     *
     * @param string $symbol The name of the function to find children of.
     * @return array An array of child methods.
     */
    protected function _getChildren($symbol) {
        $children = array();
        if (!isset($this->_indexed[$symbol])) {
            return $children;
        }
        foreach ($this->_indexed[$symbol] as $name => $data) {
            $children[] = $data + array('function' => $name);
        }
        return $children;
    }


    /**
     * Generate the approximate exclusive values for each metric.
     *
     * We get a==>b as the name, we need a key for a and b in the array
     * to get exclusive values for A we need to subtract the values of B (and any other children);
     * call passing in the entire profile only, should return an array of
     * functions with their regular timing, and exclusive numbers inside ['exclusive']
     *
     * Consider:
     *              /---c---d---e
     *          a -/----b---d---e
     *
     * We have c==>d and b==>d, and in both instances d invokes e, yet we will
     * have but a single d==>e result. This is a known and documented limitation of XHProf
     *
     * We have one d==>e entry, with some values, including ct=2
     * We also have c==>d and b==>d
     *
     * We should determine how many ==>d options there are, and equally
     * split the cost of d==>e across them since d==>e represents the sum total of all calls.
     *
     * Notes:
     *  Function names are not unique, but we're merging them
     *
     * @return Xhgui_Profile A new instance with exclusive data set.
     */
    public function calculateSelf()
    {
        // Init exclusive values
        foreach ($this->_collapsed as &$data) {
            $data['ewt'] = $data['wt'];
            $data['emu'] = $data['mu'];
            $data['ecpu'] = $data['cpu'];
            $data['ect'] = $data['ct'];
            $data['epmu'] = $data['pmu'];
        }
        unset($data);

        // Go over each method and remove each childs metrics
        // from the parent.
        foreach ($this->_collapsed as $name => $data) {
            $children = $this->_getChildren($name);
            foreach ($children as $child) {
                $this->_collapsed[$name]['ewt'] -= $child['wt'];
                $this->_collapsed[$name]['emu'] -= $child['mu'];
                $this->_collapsed[$name]['ecpu'] -= $child['cpu'];
                $this->_collapsed[$name]['ect'] -= $child['ct'];
                $this->_collapsed[$name]['epmu'] -= $child['pmu'];
            }
        }
        return $this;
    }

    /**
     * Sort data by a dimension.
     *
     * @param string $dimension The dimension to sort by.
     * @param array $data The data to sort.
     * @return array The sorted data.
     */
    public function sort($dimension, $data)
    {
        $sorter = function ($a, $b) use ($dimension) {
            if ($a[$dimension] == $b[$dimension]) {
                return 0;
            }
            return $a[$dimension] > $b[$dimension] ? -1 : 1;
        };
        uasort($data, $sorter);
        return $data;
    }

    /**
     * Split a key name into the parent==>child format.
     *
     * @param string $name The name to split.
     * @return array An array of parent, child. parent will be null if there
     *    is no parent.
     */
    public function splitName($name)
    {
        $a = explode("==>", $name);
        if (isset($a[1])) {
            return $a;
        }
        return array(null, $a[0]);
    }
}

<?php
namespace XhprofMe;

class Client
{
    /**
     * Init profiling
     */
    static public function init($url)
    {
        xhprof_enable(XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_CPU);
        register_shutdown_function(
            function () use ($url) {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                $data = json_encode(xhprof_disable(), JSON_PRETTY_PRINT);
               // $data = gzencode($json);

                $url = $url . '?' . http_build_query(
                        array(
                            'host' => $_SERVER['HTTP_HOST'],
                            'uri' => $_SERVER['PATH_INFO'],
                            'timestamp' => $_SERVER['REQUEST_TIME'],
                        )
                    );
                $context_options = array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => implode(
                                "\r\n",
                                array(
                                    "Content-encoding: gzip",
                                    "Content-type: application/json",
                                    "Content-length: " . strlen($data),
                                 //   "Cookie: XDEBUG_SESSION=phpstorm",
                                )
                            ) . "\r\n",
                        'content' => $data
                    )
                );
                $ctx = stream_context_create($context_options);
                //echo file_get_contents($url, null, $ctx);
                $fp = fopen($url, 'rb', false, $ctx);
                fclose($fp);
            }
        );
    }
}

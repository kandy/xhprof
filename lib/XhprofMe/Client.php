<?php
namespace XhprofMe;

class Client
{
    /**
     * Init profiling
     */
    static public function init()
    {
        xhprof_enable(XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_CPU);
        register_shutdown_function(
            function () {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                $data = gzencode(json_encode(xhprof_disable(), JSON_PRETTY_PRINT));

                $url = 'http://localhost/workspace/xhprof.my/?' . http_build_query(
                        array(
                            'host' => $_SERVER['HTTP_HOST'],
                            'uri' => $_SERVER['REQUEST_URI'],
                            'timestamp' => $_SERVER['REQUEST_TIME'],
                        )
                    );
                $context_options = array(
                    'http' => array(
                        'protocol_version' => 1.1,
                        'timeout' => 0.1,
                        'method' => 'POST',
                        'header' => implode(
                                "\r\n",
                                array(
                                    "Content-encoding: gzip",
                                    "Content-type: application/json",
                                    "Content-length: " . strlen($data),
                                    "Connection: Close",
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

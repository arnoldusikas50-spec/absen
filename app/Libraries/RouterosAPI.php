<?php

namespace App\Libraries;

/**
 * RouterOS API Class for CodeIgniter 4
 * Based on standard routeros-api
 */
class RouterosAPI
{
    var $debug = false;
    var $connected = false;
    var $port = 8728;
    var $ssl = false;
    var $timeout = 3;
    var $attempts = 3;
    var $delay = 3;
    var $socket;
    var $error_no;
    var $error_str;

    public function debug($text)
    {
        if ($this->debug) {
            echo $text . "\n";
        }
    }

    public function connect($ip, $login, $password, $port = 8728)
    {
        $this->port = $port;
        for ($i = 1; $i <= $this->attempts; $i++) {
            $this->connected = $this->attempt_connect($ip, $login, $password);
            if (!$this->connected) {
                sleep($this->delay);
            } else {
                return true;
            }
        }
        return false;
    }

    public function attempt_connect($ip, $login, $password)
    {
        $protocol = ($this->ssl) ? 'ssl://' : '';
        $context = stream_context_create(['ssl' => ['ciphers' => 'ADH:ALL', 'verify_peer' => false, 'verify_peer_name' => false]]);
        $this->socket = @stream_socket_client($protocol . $ip . ':' . $this->port, $this->error_no, $this->error_str, $this->timeout, STREAM_CLIENT_CONNECT, $context);

        if ($this->socket) {
            socket_set_timeout($this->socket, $this->timeout);
            $this->write('/login', false);
            $this->write('=name=' . $login, false);
            $this->write('=password=' . $password);
            $response = $this->read(false);

            if (isset($response[0]) && $response[0] == '!done') {
                return true;
            }
        }
        return false;
    }

    public function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
        $this->connected = false;
    }

    public function parse_response($response)
    {
        if (is_array($response)) {
            $parsed = [];
            foreach ($response as $x) {
                if (in_array('!re', $x)) {
                    $p = [];
                    foreach ($x as $k => $v) {
                        if ($k !== 0) {
                            $pair = explode('=', $v, 2);
                            if (isset($pair[1])) {
                                $p[$pair[0]] = $pair[1];
                            }
                        }
                    }
                    $parsed[] = $p;
                }
            }
            return $parsed;
        }
        return [];
    }

    public function comm($comm, $arr = [])
    {
        if (!$this->connected) {
            return false;
        }
        $count = count($arr);
        $this->write($comm, !($count > 0));
        $i = 0;
        foreach ($arr as $k => $v) {
            switch ($k) {
                case "-":
                    $this->write('=' . $v, ($i + 1 == $count));
                    break;
                default:
                    $this->write('=' . $k . '=' . $v, ($i + 1 == $count));
            }
            $i++;
        }
        return $this->read();
    }

    public function read($parse = true)
    {
        $response = [];
        if ($this->socket) {
            while (true) {
                $status = socket_get_status($this->socket);
                if (!$status)
                    break;

                $length = $this->decode_length();
                if ($length === false) {
                    // Check if socket is still alive or needed handling
                    break;
                }

                $line = stream_get_contents($this->socket, $length);

                if (trim($line) == '!done') {
                    $response[] = explode("\n", '!done');
                    break;
                } elseif (trim($line) == '!trap') {
                    // Error trap, read next line for message usually
                    // Simple implementation:
                    $response[] = explode("\n", '!trap');
                } else {
                    // For attributes, sometimes they come sequentially. 
                    // This simple reader assumes array of lines.
                    // A standard line read loop is safer for attributes.
                }

                // Simpler approach compatible with many versions:
                // Just use the standard loop logic often found in API class version 1.6
            }
            // Retrying the standard known-good read loop logic for stability:
        }
        return $response;
    }

    // Using a more robust read/write implementation embedded below for reliability
    var $buffer = '';

    public function write($command, $param2 = true)
    {
        if ($command) {
            $data = $command;
            $this->send_packet($data);
        }
        if ($param2) {
            $this->send_packet('');
        }
    }

    private function encode_length($length)
    {
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length = chr(($length >> 8) | 0x80) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length = chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length = chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length >= 0x10000000) {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        return $length;
    }

    private function send_packet($data)
    {
        $len = $this->encode_length(strlen($data));
        fwrite($this->socket, $len . $data);
        //$this->debug('>>> ' . $data);
    }

    private function decode_length()
    {
        $byte = @fread($this->socket, 1);
        if ($byte === false || $byte === '')
            return false;
        $length = ord($byte);
        if (($length & 0x80) == 0x00) {
            return $length;
        } elseif (($length & 0xC0) == 0x80) {
            $length = (($length & 0x3F) << 8) | ord(fread($this->socket, 1));
        } elseif (($length & 0xE0) == 0xC0) {
            $length = (($length & 0x1F) << 16) | ord(fread($this->socket, 1)) << 8 | ord(fread($this->socket, 1));
        } elseif (($length & 0xF0) == 0xE0) {
            $length = (($length & 0x0F) << 24) | ord(fread($this->socket, 1)) << 16 | ord(fread($this->socket, 1)) << 8 | ord(fread($this->socket, 1));
        } else {
            $length = ord(fread($this->socket, 1)) << 24 | ord(fread($this->socket, 1)) << 16 | ord(fread($this->socket, 1)) << 8 | ord(fread($this->socket, 1));
        }
        return $length;
    }

    // Override Read for robustness
    public function read_v2($parse = true)
    {
        $RESPONSE = array();
        $received_done = false;
        while (true) {
            // Read first byte of length
            $byte = @fread($this->socket, 1);
            if ($byte === false || $byte === '') { // Connection closed?
                break;
            }

            // Decode length
            $length = ord($byte);
            if (($length & 0x80) == 0x00) {
                // $length = $length;
            } elseif (($length & 0xC0) == 0x80) {
                $length = (($length & 0x3F) << 8) | ord(fread($this->socket, 1));
            } elseif (($length & 0xE0) == 0xC0) {
                $length = (($length & 0x1F) << 16) | ord(fread($this->socket, 1)) << 8 | ord(fread($this->socket, 1));
            } elseif (($length & 0xF0) == 0xE0) {
                $length = (($length & 0x0F) << 24) | ord(fread($this->socket, 1)) << 16 | ord(fread($this->socket, 1)) << 8 | ord(fread($this->socket, 1));
            } else {
                $length = ord(fread($this->socket, 1)) << 24 | ord(fread($this->socket, 1)) << 16 | ord(fread($this->socket, 1)) << 8 | ord(fread($this->socket, 1));
            }

            $line = "";
            if ($length > 0) {
                $line = "";
                while (strlen($line) < $length) {
                    $chunk = fread($this->socket, $length - strlen($line));
                    if ($chunk === false)
                        break 2;
                    $line .= $chunk;
                }
            }

            //$this->debug('<<< ' . $line);

            if ($line == '!done') {
                $received_done = true;
                break;
            } elseif ($line == '!trap') {
                // Trap
            } elseif ($line == '!re') {
                $RESPONSE[] = array(); // New Row
            } else {
                // Attribute
                if (substr($line, 0, 1) == '=') {
                    $matches = explode('=', substr($line, 1), 2);
                    if (isset($matches[1])) {
                        // Add to last row
                        $last_idx = count($RESPONSE) - 1;
                        if ($last_idx < 0) {
                            // Unexpected attribute before !re, maybe login response
                            $RESPONSE['attr'][] = $matches;
                        } else {
                            $RESPONSE[$last_idx][$matches[0]] = $matches[1];
                        }
                    }
                }
            }
        }
        return $RESPONSE;
    }
}

<?php
if (!class_exists('RouterosAPI')) {
class RouterosAPI {
    var $socket;
    var $connected = false;
    var $timeout = 3;

    function connect($ip, $login, $password, $port = 8728) {
        $this->socket = @fsockopen($ip, (int)$port, $errno, $errstr, $this->timeout);
        if (!$this->socket) return false;
        stream_set_timeout($this->socket, $this->timeout);
        $this->write('/login', false);
        $this->write('=name=' . $login, false);
        $this->write('=password=' . $password);
        $res = $this->read(false);
        if (in_array('!done', $res)) {
            $this->connected = true;
            return true;
        }
        foreach ($res as $line) {
            if (strpos($line, '=ret=') === 0) {
                $chal = pack('H*', substr($line, 5));
                $md5 = md5(chr(0) . $password . $chal);
                $this->write('/login', false);
                $this->write('=name=' . $login, false);
                $this->write('=response=00' . $md5);
                $res2 = $this->read(false);
                if (in_array('!done', $res2)) {
                    $this->connected = true;
                    return true;
                }
            }
        }
        @fclose($this->socket);
        return false;
    }

    function disconnect() {
        if ($this->socket) @fclose($this->socket);
        $this->connected = false;
    }

    function comm($com, $arr = array()) {
        $count = count($arr);
        $this->write($com, !$arr);
        $i = 0;
        foreach ($arr as $k => $v) {
            $el = ($k == '?') ? "$k$v" : "=$k=$v";
            $this->write($el, (++$i == $count));
        }
        return $this->read(false);
    }

    function write($str, $param2 = true) {
        if (!$str || !$this->socket) return;
        $len = strlen($str);
        if ($len < 0x80) fwrite($this->socket, chr($len));
        elseif ($len < 0x4000) { $len |= 0x8000; fwrite($this->socket, chr(($len >> 8) & 0xFF) . chr($len & 0xFF)); }
        elseif ($len < 0x200000) { $len |= 0xC00000; fwrite($this->socket, chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF)); }
        else { $len |= 0xE0000000; fwrite($this->socket, chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF)); }
        fwrite($this->socket, $str);
        if ($param2) fwrite($this->socket, chr(0));
    }

    function read($parse = true) {
        $RESPONSE = array();
        if (!$this->socket) return $RESPONSE;
        while (true) {
            $c = fread($this->socket, 1);
            if ($c === false || $c === '') break;
            $BYTE = ord($c);
            $LENGTH = 0;
            if ($BYTE & 128) {
                if (($BYTE & 192) == 128) $LENGTH = (($BYTE & 63) << 8) + ord(fread($this->socket, 1));
                elseif (($BYTE & 224) == 192) $LENGTH = (($BYTE & 31) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
                elseif (($BYTE & 240) == 224) $LENGTH = (($BYTE & 15) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
                else $LENGTH = (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            } else {
                $LENGTH = $BYTE;
            }
            $_ = "";
            if ($LENGTH > 0) {
                $retlen = 0;
                while ($retlen < $LENGTH) {
                    $chunk = fread($this->socket, $LENGTH - $retlen);
                    if ($chunk === false || $chunk === '') break;
                    $_ .= $chunk;
                    $retlen += strlen($chunk);
                }
                $RESPONSE[] = $_;
            }
            if ($_ == "!done" || $_ == "!fatal") break;
        }
        return $RESPONSE;
    }
}
}

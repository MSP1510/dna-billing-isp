<?php
class RouterosAPI {
    var $socket; var $connected = false;
    function connect($ip, $login, $password, $port = 8728) {
        $this->socket = @fsockopen($ip, $port, $errno, $errstr, 3);
        if (!$this->socket) return false;
        stream_set_timeout($this->socket, 3);
        $this->write('/login', false);
        $this->write('=name=' . $login, false);
        $this->write('=password=' . $password);
        $res = $this->read();
        if (isset($res[0]) && $res[0] == '!done') { $this->connected = true; return true; }
        return false;
    }
    function write($cmd, $param2 = true) {
        $len = strlen($cmd);
        if ($len < 0x80) fwrite($this->socket, chr($len));
        elseif ($len < 0x4000) { $len |= 0x8000; fwrite($this->socket, chr(($len >> 8) & 0xFF) . chr($len & 0xFF)); }
        fwrite($this->socket, $cmd);
        if ($param2) fwrite($this->socket, chr(0));
    }
    function read() {
        $resp = [];
        while (true) {
            $c = @fread($this->socket, 1); if ($c === '' || $c === false) break;
            $byte = ord($c);
            if ($byte & 0x80) { $len = (($byte & 0x3F) << 8) + ord(fread($this->socket, 1)); } else { $len = $byte; }
            if ($len == 0) { if (in_array('!done', $resp) || in_array('!trap', $resp)) break; continue; }
            $resp[] = fread($this->socket, $len);
        }
        return $resp;
    }
    function comm($com, $arr = []) {
        $this->write($com, empty($arr));
        $i = 0; $c = count($arr);
        foreach ($arr as $k => $v) { $this->write('=' . $k . '=' . $v, (++$i == $c)); }
        return $this->read();
    }
    function disconnect() { if ($this->socket) fclose($this->socket); }
}
?>

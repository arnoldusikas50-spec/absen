/*
 * Jam acuan WITA untuk tampilan presensi.
 *
 * Server selalu memakai Asia/Makassar (WITA), sedangkan `new Date()` di
 * browser mengikuti zona waktu perangkat. Di perangkat WIB/WIT, jam "WITA"
 * di layar melenceng 1-2 jam dan jendela buka/tutup scan QR ikut waktu
 * perangkat. Halaman pemanggil memanggil witaInit() dengan stamp dari server
 * (date('Y-m-d H:i:s')); selisihnya disimpan, lalu semua kebutuhan waktu
 * diambil dari witaNow()/witaHMS()/witaYMD().
 *
 * Tanpa witaInit() (atau stamp tidak terbaca), semua fungsi jatuh kembali ke
 * waktu perangkat — sama seperti perilaku lama, tidak pernah mengembalikan
 * NaN atau jam kosong.
 */
(function (w) {
    var offsetMs = 0;
    var aktif = false;

    function parseWita(stamp) {
        var s = String(stamp || '').trim();
        // 'YYYY-MM-DD HH:MM:SS' / 'YYYY-MM-DDTHH:MM:SS' — selalu WITA (UTC+8).
        if (!/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/.test(s)) return NaN;
        var iso = s.replace(' ', 'T');
        if (!/[Zz]|[+-]\d{2}:?\d{2}$/.test(iso)) iso += '+08:00';
        return Date.parse(iso);
    }

    w.witaInit = function (stamp) {
        var ts = parseWita(stamp);
        if (isNaN(ts)) { aktif = false; offsetMs = 0; return false; }
        offsetMs = ts - Date.now();
        aktif = true;
        return true;
    };

    w.witaAktif = function () { return aktif; };
    w.witaNow = function () { return new Date(Date.now() + offsetMs); };

    function p(n) { return String(n).padStart(2, '0'); }

    // 'HH:MM:SS' dan 'HH:MM' menurut jam server.
    w.witaHMS = function () {
        var d = w.witaNow();
        return p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    };
    w.witaHM = function () { return w.witaHMS().slice(0, 5); };
    w.witaYMD = function () {
        var d = w.witaNow();
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
    };
})(window);

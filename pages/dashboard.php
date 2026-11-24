<?php
$routers_q = mysqli_query($conn, "SELECT id, nama_nas, ip_address FROM routers ORDER BY id ASC");
$router_list = [];
while ($r = mysqli_fetch_assoc($routers_q)) $router_list[] = $r;

// Auto-hitung Arus Kas & Pemasangan Baru Bulan Ini dari tabel yang ada
$tot_masuk = 0; $tot_keluar = 0; $pelanggan_baru_bln = 0;
$bln_ini = date('Y-m');
$tbls = [];
$qt = mysqli_query($conn, "SHOW TABLES");
while ($rtb = mysqli_fetch_row($qt)) $tbls[] = $rtb;

foreach ($tbls as $tb) {
    if (in_array($tb, ['kas', 'keuangan', 'transaksi', 'arus_kas'])) {
        $q1 = @mysqli_query($conn, "SELECT SUM(nominal) AS jml FROM `$tb` WHERE jenis IN ('masuk','pemasukan','in') OR tipe IN ('masuk','pemasukan')");
        if ($q1 && $d1 = mysqli_fetch_assoc($q1)) $tot_masuk += (float)$d1['jml'];
        $q2 = @mysqli_query($conn, "SELECT SUM(nominal) AS jml FROM `$tb` WHERE jenis IN ('keluar','pengeluaran','out') OR tipe IN ('keluar','pengeluaran')");
        if ($q2 && $d2 = mysqli_fetch_assoc($q2)) $tot_keluar += (float)$d2['jml'];
    }
    if (in_array($tb, ['invoice', 'invoices', 'tagihan']) && $tot_masuk == 0) {
        $qi = @mysqli_query($conn, "SELECT SUM(nominal) AS jml FROM `$tb` WHERE status IN ('lunas','paid','Lunas')");
        if ($qi && $di = mysqli_fetch_assoc($qi)) $tot_masuk += (float)$di['jml'];
        else {
            $qi2 = @mysqli_query($conn, "SELECT SUM(jumlah) AS jml FROM `$tb` WHERE status IN ('lunas','paid','Lunas')");
            if ($qi2 && $di2 = mysqli_fetch_assoc($qi2)) $tot_masuk += (float)$di2['jml'];
        }
    }
    if (in_array($tb, ['pelanggan', 'customers', 'users_pppoe', 'client'])) {
        foreach (['tgl_pasang', 'tanggal_pasang', 'created_at', 'tgl_daftar', 'tanggal'] as $col) {
            $qp = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$tb` WHERE `$col` LIKE '$bln_ini%'");
            if ($qp && $dp = mysqli_fetch_assoc($qp)) { $pelanggan_baru_bln = (int)$dp['c']; break; }
        }
    }
}
$sisa_saldo = $tot_masuk - $tot_keluar;
?>
<style>
.dna-wrap{background:#0d1117;color:#e6edf3;padding:18px;border-radius:14px;font-family:sans-serif}
.dna-row4{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:16px}
.dna-row2{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;margin-bottom:18px}
.dna-card{background:#161b22;border:1px solid #21262d;border-radius:12px;padding:16px}
.dna-lbl{font-size:12px;color:#8b949e;margin-bottom:6px;display:flex;justify-content:space-between}
.dna-num{font-size:21px;font-weight:700}
.dna-sub-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px;text-align:center}
.dna-box{background:#0d1117;border:1px solid #21262d;border-radius:8px;padding:10px 6px}
.dna-top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin:8px 0 14px;padding-top:14px;border-top:1px solid #21262d}
.dna-sel{background:#161b22;color:#e6edf3;border:1px solid #30363d;padding:7px 12px;border-radius:8px;font-size:13px}
.dna-bdg{padding:5px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(16,185,129,.15);color:#3fb950}
.dna-bdg.off{background:rgba(248,81,73,.15);color:#f85149}
.dna-g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media(max-width:768px){.dna-g3{grid-template-columns:1fr}}
.dna-pill{font-size:10px;padding:2px 7px;border-radius:10px;background:rgba(56,189,248,.15);color:#38bdf8}
.dna-trk{height:6px;background:#21262d;border-radius:4px;overflow:hidden;margin-top:8px}
.dna-fil{height:100%;transition:width .4s ease}
.dna-tbl{width:100%;border-collapse:collapse;font-size:12px;margin-top:10px}
.dna-tbl th{text-align:left;color:#8b949e;padding-bottom:8px;border-bottom:1px solid #21262d}
.dna-tbl td{padding:8px 4px;border-bottom:1px solid #1c2128}
.dna-ring{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border:2px solid #38bdf8;margin-left:auto}
</style>
<div class="dna-wrap">
  <!-- BARIS 1: ARUS KAS & PEMASANGAN BARU BULANAN -->
  <div class="dna-row4">
    <div class="dna-card" style="border-left:3px solid #10b981">
      <div class="dna-lbl"><span>Arus Kas Masuk</span><span style="color:#10b981">▲ IN</span></div>
      <div class="dna-num" style="color:#4ade80">Rp <?= number_format($tot_masuk,0,',','.') ?></div>
      <small style="color:#8b949e">Total pemasukan tercatat</small>
    </div>
    <div class="dna-card" style="border-left:3px solid #ef4444">
      <div class="dna-lbl"><span>Arus Kas Keluar</span><span style="color:#ef4444">▼ OUT</span></div>
      <div class="dna-num" style="color:#f87171">Rp <?= number_format($tot_keluar,0,',','.') ?></div>
      <small style="color:#8b949e">Biaya operasional keluar</small>
    </div>
    <div class="dna-card" style="border-left:3px solid #38bdf8">
      <div class="dna-lbl"><span>Sisa Saldo Bersih</span><span style="color:#38bdf8">NET</span></div>
      <div class="dna-num" style="color:#38bdf8">Rp <?= number_format($sisa_saldo,0,',','.') ?></div>
      <small style="color:#8b949e">Selisih kas masuk &amp; keluar</small>
    </div>
    <div class="dna-card" style="border-left:3px solid #f59e0b">
      <div class="dna-lbl"><span>Pemasangan Baru</span><span style="color:#f59e0b"><?= date('M Y') ?></span></div>
      <div class="dna-num" style="color:#fbbf24"><?= $pelanggan_baru_bln ?> Pelanggan</div>
      <small style="color:#8b949e">Aktivasi baru bulan ini</small>
    </div>
  </div>

  <!-- BARIS 2: RINCIAN PELANGGAN PPPOE & HOTSPOT VOUCHER -->
  <div class="dna-row2">
    <div class="dna-card">
      <div class="dna-lbl"><strong style="color:#f0f6fc;font-size:14px">Pelanggan PPPoE</strong><span id="ppp-tot" style="color:#8b949e">Total: 0</span></div>
      <div class="dna-sub-grid">
        <div class="dna-box"><small style="color:#4ade80">● Online</small><div id="ppp-on" style="font-size:20px;font-weight:700;color:#4ade80;margin-top:4px">0</div></div>
        <div class="dna-box"><small style="color:#94a3b8">● Offline</small><div id="ppp-off" style="font-size:20px;font-weight:700;color:#cbd5e1;margin-top:4px">0</div></div>
        <div class="dna-box"><small style="color:#f87171">● Isolir</small><div id="ppp-iso" style="font-size:20px;font-weight:700;color:#f87171;margin-top:4px">0</div></div>
      </div>
    </div>
    <div class="dna-card">
      <div class="dna-lbl"><strong style="color:#f0f6fc;font-size:14px">Hotspot Voucher</strong><span id="hs-tot" style="color:#8b949e">Total: 0</span></div>
      <div class="dna-sub-grid" style="grid-template-columns:repeat(2,1fr)">
        <div class="dna-box"><small style="color:#38bdf8">● Voucher Online</small><div id="hs-on" style="font-size:20px;font-weight:700;color:#38bdf8;margin-top:4px">0</div></div>
        <div class="dna-box"><small style="color:#f59e0b">● Offline / Stok</small><div id="hs-off" style="font-size:20px;font-weight:700;color:#fbbf24;margin-top:4px">0</div></div>
      </div>
    </div>
  </div>

  <!-- BARIS 3: TRAFFIC LIVE & INTERFACES (DIPINDAHKAN KE BAWAH) -->
  <div class="dna-top">
    <div><h3 style="margin:0;font-size:17px;color:#f0f6fc">Interfaces &amp; Traffic Live</h3><small style="color:#8b949e">Real-time bandwidth &amp; profil aktif router terpilih</small></div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <select id="sel-router" class="dna-sel"><?php foreach($router_list as $rt): ?><option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['nama_nas']) ?></option><?php endforeach; ?></select>
      <select id="sel-iface" class="dna-sel"><option value="">Memuat interface...</option></select>
      <span id="status-badge" class="dna-bdg">● Memeriksa...</span>
    </div>
  </div>

  <div class="dna-row2">
    <div class="dna-g3">
      <div class="dna-card">
        <div style="font-size:12px;color:#8b949e">▲ Upload (TX)</div>
        <div id="val-tx" class="dna-num" style="color:#38bdf8;margin:4px 0">0.00 Mbps</div>
        <span id="pct-tx" class="dna-pill">0%</span>
        <svg viewBox="0 0 120 70" width="115" height="68" style="display:block;margin:6px auto">
          <path d="M15 60 A45 45 0 0 1 45 20" fill="none" stroke="#10b981" stroke-width="10" stroke-linecap="round"/>
          <path d="M45 20 A45 45 0 0 1 85 25" fill="none" stroke="#f59e0b" stroke-width="10"/>
          <path d="M85 25 A45 45 0 0 1 105 60" fill="none" stroke="#ef4444" stroke-width="10" stroke-linecap="round"/>
          <line id="needle-tx" x1="60" y1="60" x2="22" y2="60" stroke="#38bdf8" stroke-width="3" stroke-linecap="round" style="transform-origin:60px 60px;transition:transform .5s"/>
          <circle cx="60" cy="60" r="5" fill="#e6edf3"/>
        </svg>
        <div class="dna-trk"><div id="bar-tx" class="dna-fil" style="width:0%;background:#4ade80"></div></div>
      </div>
      <div class="dna-card">
        <div style="font-size:12px;color:#8b949e">▼ Download (RX)</div>
        <div id="val-rx" class="dna-num" style="color:#60a5fa;margin:4px 0">0.00 Mbps</div>
        <span id="pct-rx" class="dna-pill">0%</span>
        <svg viewBox="0 0 120 70" width="115" height="68" style="display:block;margin:6px auto">
          <path d="M15 60 A45 45 0 0 1 45 20" fill="none" stroke="#10b981" stroke-width="10" stroke-linecap="round"/>
          <path d="M45 20 A45 45 0 0 1 85 25" fill="none" stroke="#f59e0b" stroke-width="10"/>
          <path d="M85 25 A45 45 0 0 1 105 60" fill="none" stroke="#ef4444" stroke-width="10" stroke-linecap="round"/>
          <line id="needle-rx" x1="60" y1="60" x2="22" y2="60" stroke="#60a5fa" stroke-width="3" stroke-linecap="round" style="transform-origin:60px 60px;transition:transform .5s"/>
          <circle cx="60" cy="60" r="5" fill="#e6edf3"/>
        </svg>
        <div class="dna-trk"><div id="bar-rx" class="dna-fil" style="width:0%;background:#f59e0b"></div></div>
      </div>
      <div class="dna-card">
        <div style="font-size:12px;color:#8b949e">● Total Online</div>
        <div id="val-online" class="dna-num" style="color:#4ade80;margin:4px 0">0 Online</div>
        <span id="pct-online" class="dna-pill" style="background:rgba(74,222,128,.15);color:#4ade80">0%</span>
        <svg viewBox="0 0 120 70" width="115" height="68" style="display:block;margin:6px auto">
          <path d="M15 60 A45 45 0 0 1 45 20" fill="none" stroke="#10b981" stroke-width="10" stroke-linecap="round"/>
          <path d="M45 20 A45 45 0 0 1 85 25" fill="none" stroke="#f59e0b" stroke-width="10"/>
          <path d="M85 25 A45 45 0 0 1 105 60" fill="none" stroke="#ef4444" stroke-width="10" stroke-linecap="round"/>
          <line id="needle-online" x1="60" y1="60" x2="22" y2="60" stroke="#4ade80" stroke-width="3" stroke-linecap="round" style="transform-origin:60px 60px;transition:transform .5s"/>
          <circle cx="60" cy="60" r="5" fill="#e6edf3"/>
        </svg>
        <div class="dna-trk"><div id="bar-online" class="dna-fil" style="width:0%;background:#38bdf8"></div></div>
      </div>
    </div>

    <div class="dna-card">
      <div style="font-size:14px;font-weight:600">Bandwidth &amp; Paket Aktif (PPPoE)</div>
      <table class="dna-tbl">
        <thead><tr><th>PAKET</th><th>RASIO ONLINE</th><th style="text-align:right">AKTIF</th></tr></thead>
        <tbody id="pkg-table-body"><tr><td colspan="3" style="color:#8b949e;text-align:center;padding:16px">Menghubungkan ke MikroTik...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>
<script>
(function(){
  const maxMbps=200;let busy=false,lastRid=null;
  function gauge(n,b,p,v,m){
    const r=Math.min(Math.max(v/Math.max(1,m),0),1);
    document.getElementById(n).style.transform=`rotate(${Math.round(r*180)}deg)`;
    document.getElementById(b).style.width=`${Math.round(r*100)}%`;
    document.getElementById(p).textContent=`${Math.round(r*100)}%`;
  }
  function sync(){
    if(busy)return;
    const rs=document.getElementById('sel-router'),is=document.getElementById('sel-iface');
    if(!rs)return;
    const rid=rs.value,ifc=(lastRid===rid&&is)?is.value:'';
    busy=true;
    fetch(`api_traffic.php?router_id=${encodeURIComponent(rid)}&iface=${encodeURIComponent(ifc)}`)
    .then(r=>r.json()).then(d=>{
      const bg=document.getElementById('status-badge');
      bg.textContent=d.status==='Online'?'● Online':'● Offline';
      bg.classList.toggle('off',d.status!=='Online');
      if(lastRid!==rid&&d.interfaces&&d.interfaces.length){
        is.innerHTML=d.interfaces.map(i=>`<option value="${i}" ${i===d.selected_if?'selected':''}>${i}</option>`).join('');
        lastRid=rid;
      }
      document.getElementById('ppp-tot').textContent=`Total: ${d.ppp_tot}`;
      document.getElementById('ppp-on').textContent=d.ppp_on;
      document.getElementById('ppp-off').textContent=d.ppp_off;
      document.getElementById('ppp-iso').textContent=d.ppp_isolir;
      document.getElementById('hs-tot').textContent=`Total: ${d.hs_tot}`;
      document.getElementById('hs-on').textContent=d.hs_on;
      document.getElementById('hs-off').textContent=d.hs_off;

      const totOn=d.ppp_on+d.hs_on, totAll=d.ppp_tot+d.hs_tot;
      document.getElementById('val-tx').textContent=`${d.tx.toFixed(2)} Mbps`;
      document.getElementById('val-rx').textContent=`${d.rx.toFixed(2)} Mbps`;
      document.getElementById('val-online').textContent=`${totOn} Online`;
      gauge('needle-tx','bar-tx','pct-tx',d.tx,maxMbps);
      gauge('needle-rx','bar-rx','pct-rx',d.rx,maxMbps);
      gauge('needle-online','bar-online','pct-online',totOn,totAll);

      const tb=document.getElementById('pkg-table-body');
      if(d.packages&&d.packages.length){
        tb.innerHTML=d.packages.map(p=>{
          const pr=Math.round((p.online/Math.max(1,p.total))*100);
          return `<tr><td><strong style="color:#f0f6fc">${p.name}</strong></td><td><div style="font-size:11px;color:#8b949e;margin-bottom:3px">${p.online}/${p.total} (${pr}%)</div><div class="dna-trk"><div class="dna-fil" style="width:${pr}%;background:#38bdf8"></div></div></td><td><div class="dna-ring">${p.online}</div></td></tr>`;
        }).join('');
      } else {
        tb.innerHTML='<tr><td colspan="3" style="color:#8b949e;text-align:center;padding:14px">Tidak ada paket PPPoE ditemukan</td></tr>';
      }
    }).catch(()=>{}).finally(()=>{busy=false;});
  }
  document.getElementById('sel-router').addEventListener('change',()=>{lastRid=null;sync();});
  document.getElementById('sel-iface').addEventListener('change',sync);
  sync();setInterval(sync,3000);
})();
</script>

/**
 * SIMRAN — Standar Biaya SBM (pencarian live). JavaScript murni.
 * Semua teks dari server disisipkan lewat textContent (aman dari XSS).
 */
(function () {
    const $ = (id) => document.getElementById(id);
    const q = $('sbmQ'), reg = $('sbmReg'), prov = $('sbmProv');
    const box = $('sbmResults'), banner = $('sbmBanner');
    if (!q) return;

    const PROV_KEY = 'sbm-prov';
    const rupiah = (n) => 'Rp' + Number(n).toLocaleString('id-ID');
    const fmt = (b) => {
        if (b.besaran === null) return b.keterangan || '-';
        return b.mata_uang === 'USD' ? 'US$' + Number(b.besaran).toLocaleString('en-US') : rupiah(b.besaran);
    };
    const el = (tag, cls, text) => {
        const e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text !== undefined && text !== null) e.textContent = text;
        return e;
    };

    try {
        const saved = localStorage.getItem(PROV_KEY);
        if (saved && prov.querySelector('option[value="' + CSS.escape(saved) + '"]')) prov.value = saved;
    } catch (e) { /* abaikan */ }

    // ---- favorit & terakhir dilihat (localStorage, aman bila diblokir) ----
    const FAV_KEY = 'sbm-fav', REC_KEY = 'sbm-recent';
    const load = (k) => { try { const v = JSON.parse(localStorage.getItem(k) || '[]'); return Array.isArray(v) ? v : []; } catch (e) { return []; } };
    const save = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* abaikan */ } };
    const keyOf = (item) => item.kanonik || ('id' + item.id);
    const entry = (item) => ({ key: keyOf(item), nama: item.nama });
    const shortName = (n) => n.replace(/^Satuan Biaya\s+/i, '');
    const isFav = (item) => load(FAV_KEY).some((f) => f.key === keyOf(item));
    function toggleFav(item) {
        let favs = load(FAV_KEY);
        favs = favs.some((f) => f.key === keyOf(item)) ? favs.filter((f) => f.key !== keyOf(item)) : [entry(item)].concat(favs).slice(0, 20);
        save(FAV_KEY, favs);
        renderQuick();
    }
    function pushRecent(item) {
        const rec = [entry(item)].concat(load(REC_KEY).filter((r) => r.key !== keyOf(item))).slice(0, 8);
        save(REC_KEY, rec);
        renderQuick();
    }
    function chipList(box, list) {
        box.replaceChildren();
        list.forEach((e) => {
            const b = el('button', 'sbm-chip', shortName(e.nama));
            b.type = 'button';
            b.title = e.nama;
            b.addEventListener('click', () => { q.value = shortName(e.nama); run(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
            box.append(b);
        });
    }
    function renderQuick() {
        const favs = load(FAV_KEY), rec = load(REC_KEY).filter((r) => !favs.some((f) => f.key === r.key));
        $('sbmFavBox').style.display = favs.length ? '' : 'none';
        $('sbmRecentBox').style.display = rec.length ? '' : 'none';
        $('sbmQuick').style.display = favs.length || rec.length ? '' : 'none';
        chipList($('sbmFavList'), favs);
        chipList($('sbmRecentList'), rec);
    }
    $('sbmClearRecent').addEventListener('click', () => { save(REC_KEY, []); renderQuick(); });

    let timer = null, seq = 0;
    function schedule() { clearTimeout(timer); timer = setTimeout(run, 250); }

    function run() {
        const term = q.value.trim();
        if (term.length < 2) {
            box.replaceChildren();
            renderBanner(null);
            return;
        }
        const mine = ++seq;
        const url = window.simranBaseUrl + '/sbm/cari?' + new URLSearchParams({ q: term, reg: reg.value, prov: prov.value });
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.json())
            .then((d) => { if (mine === seq) render(d); })
            .catch(() => { box.replaceChildren(el('div', 'sbm-empty', 'Gagal memuat data. Coba lagi.')); });
    }

    function renderBanner(regulasi) {
        banner.className = 'sbm-banner';
        banner.replaceChildren();
        if (!regulasi) {
            banner.textContent = 'Ketik kata kunci atau pilih salah satu pencarian cepat di bawah.';
            return;
        }
        const strong = el('strong', null, regulasi.label + ' (TA ' + regulasi.tahun_anggaran + ')');
        if (regulasi.punya_nominal) {
            banner.append('Angka mengacu pada ', strong, '. Untuk RKA TA 2027, nominal baru berlaku setelah ditetapkan Menteri Keuangan; ' +
                'sementara angka ini dapat dijadikan acuan.');
            if (regulasi.catatan) banner.append(' ', regulasi.catatan);
        } else {
            banner.classList.add('warn');
            banner.append(strong, ': nominal belum ditetapkan (menunggu KMK). ', regulasi.catatan || '');
        }
    }

    function render(d) {
        renderBanner(d.regulasi);
        box.replaceChildren();
        if (!d.hasil.length) {
            const empty = el('div', 'sbm-empty card-simran');
            empty.append(el('div', 'fw-semibold mb-1', 'Tidak ada hasil untuk "' + d.q + '"'));
            empty.append(el('div', null, 'Coba kata kunci lain: fullboard, fullday, halfday, uang harian, snack, konsumsi rapat.'));
            box.append(empty);
            return;
        }
        d.hasil.forEach((item, i) => box.append(card(item, d.regulasi, i === 0)));
    }

    function card(item, regulasi, open) {
        const c = el('article', 'card-simran sbm-card');
        const head = el('div', 'sbm-card-head');
        head.append(el('h3', null, item.kode + '. ' + item.nama));
        const badges = el('div', 'd-flex align-items-center gap-2');
        const star = el('button', 'btn btn-link p-0', isFav(item) ? '★' : '☆');
        star.type = 'button';
        star.title = 'Tambah/hapus favorit';
        star.setAttribute('aria-label', 'Favorit');
        star.style.cssText = 'font-size:20px;line-height:1;text-decoration:none;color:#d97706';
        star.addEventListener('click', () => { toggleFav(item); star.textContent = isFav(item) ? '★' : '☆'; });
        badges.append(star);
        badges.append(el('span', 'sbm-badge ' + (item.sifat === 'batas_tertinggi' ? 'max' : 'over'), item.sifat_label));
        head.append(badges);
        c.append(head);

        if (item.harga.length) {
            const wrap = el('div', 'sbm-table-wrap');
            const tb = el('table', 'sbm-table');
            tb.append(row(['Uraian', 'Satuan', 'Besaran'], 'th'));
            item.harga.forEach((g) => {
                const tr = el('tr', 'grp'), td = el('td', null, g.kelompok);
                td.colSpan = 3; tr.append(td); tb.append(tr);
                g.baris.forEach((b) => {
                    const r = el('tr');
                    const label = el('td', null, b.kolom);
                    if (b.needs_review) label.append(' ', el('span', 'sbm-badge review', 'perlu verifikasi'));
                    r.append(label, el('td', null, b.satuan || ''),
                        el('td', 'num', fmt(b)));
                    tb.append(r);
                });
            });
            wrap.append(tb);
            c.append(wrap);
            if (item.terpotong > 0) {
                const more = el('div', 'sbm-source', 'Menampilkan ' + (item.total_baris - item.terpotong) + ' dari ' + item.total_baris + ' baris. ');
                const ml = el('a', null, 'Lihat semua di halaman detail');
                ml.href = window.simranBaseUrl + '/sbm/item/' + item.id + '?prov=' + encodeURIComponent(prov.value) + '&q=' + encodeURIComponent(q.value.trim());
                more.append(ml);
                c.append(more);
            }
        } else if (!regulasi.punya_nominal) {
            const note = el('div', 'sbm-banner warn mt-2', 'Nominal belum ditetapkan (menunggu KMK).');
            if (item.deskripsi) note.append(el('div', 'mt-1', item.deskripsi));
            if (item.acuan) {
                const a = el('a', 'ms-1', 'Lihat angka acuan ' + item.acuan.label);
                a.href = window.simranBaseUrl + '/sbm/item/' + item.acuan.item_id;
                note.append(a);
            }
            c.append(note);
        }

        if (item.ketentuan.length) {
            const det = el('details', 'sbm-ketentuan');
            if (open) det.open = true;
            det.append(el('summary', null, 'Ketentuan & Penjelasan'));
            det.addEventListener('toggle', () => { if (det.open) pushRecent(item); });
            let ul = null;
            item.ketentuan.forEach((line) => {
                if (line.startsWith('•')) {
                    if (!ul) { ul = el('ul'); det.append(ul); }
                    ul.append(el('li', null, line.replace(/^•\s*/, '')));
                } else {
                    ul = null;
                    det.append(el('p', null, line));
                }
            });
            c.append(det);
        }

        c.append(el('div', 'sbm-source', 'Sumber: ' + item.sumber));

        const actions = el('div', 'sbm-actions');
        const detail = el('a', 'btn-simran', 'Lihat detail');
        detail.href = window.simranBaseUrl + '/sbm/item/' + item.id + '?prov=' + encodeURIComponent(prov.value);
        detail.addEventListener('click', () => pushRecent(item));
        actions.append(detail);
        if (item.harga.length) {
            const copy = el('button', 'btn-simran', 'Salin ringkasan');
            copy.type = 'button';
            copy.addEventListener('click', () => { pushRecent(item); copySummary(item, copy); });
            actions.append(copy);
        }
        c.append(actions);
        return c;
    }

    function row(cells, tag) {
        const r = el('tr');
        cells.forEach((t) => r.append(el(tag, null, t)));
        return r;
    }

    function copySummary(item, btn) {
        const provName = prov.options[prov.selectedIndex].text;
        const lines = [];
        item.harga.forEach((g) => g.baris.forEach((b) => {
            lines.push(g.kelompok + ' - ' + b.kolom + ' (' + provName + '): ' + fmt(b) + (b.satuan ? '/' + b.satuan : '') +
                ' (' + item.sifat_label + ')');
        }));
        const text = item.nama + '\n' + lines.join('\n') + '\n— ' + item.sumber;
        const done = () => { const old = btn.textContent; btn.textContent = 'Tersalin ✓'; setTimeout(() => { btn.textContent = old; }, 1500); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, () => window.prompt('Salin teks:', text));
        } else {
            window.prompt('Salin teks:', text);
        }
    }

    q.addEventListener('input', schedule);
    reg.addEventListener('change', run);
    prov.addEventListener('change', () => {
        try { localStorage.setItem(PROV_KEY, prov.value); } catch (e) { /* abaikan */ }
        run();
    });
    document.querySelectorAll('.sbm-chip').forEach((b) => b.addEventListener('click', () => {
        q.value = b.dataset.q;
        run();
    }));

    renderQuick();
    q.value = (window.sbmConfig && window.sbmConfig.defaultQuery) || '';
    run();
})();

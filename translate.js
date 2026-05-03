// ============================================================
//  translate.js — TinySteps  (à inclure dans chaque page)
//  v2 — traduction PARALLELE en batch pour vitesse maximale
// ============================================================
const SOURCE_LANG = 'fr';
const EMAIL       = 'tinysteps.app@gmail.com';
const RTL_LANGS   = ['ar'];

let _cache       = {};
let _originals   = {};
let _currentLang = localStorage.getItem('ts_lang') || 'fr';

// ── Sauvegarder les textes originaux au chargement ──────────
function _saveOriginals() {
    document.querySelectorAll('[data-key]').forEach(el => {
        if (!_originals[el.dataset.key]) {
            _originals[el.dataset.key] = el.innerText.trim();
        }
    });
}

// ── Appel API MyMemory (1 texte) ─────────────────────────────
async function _translateOne(text, lang) {
    if (!text || text.trim() === '') return text;
    const k = lang + '::' + text;
    if (_cache[k]) return _cache[k];
    try {
        const url = 'https://api.mymemory.translated.net/get'
                  + '?q='        + encodeURIComponent(text)
                  + '&langpair=' + SOURCE_LANG + '|' + lang
                  + '&de='       + EMAIL;
        const res  = await fetch(url);
        const data = await res.json();
        const t    = data.responseData.translatedText;
        _cache[k]  = t;
        return t;
    } catch (e) {
        return text;
    }
}

// ── Traduire toute la page EN PARALLELE (batch) ──────────────
async function _translatePage(lang) {
    document.body.dir = RTL_LANGS.includes(lang) ? 'rtl' : 'ltr';

    if (lang === SOURCE_LANG) {
        document.querySelectorAll('[data-key]').forEach(el => {
            el.innerText = _originals[el.dataset.key] || el.innerText;
        });
        // Restore placeholders
        document.querySelectorAll('[data-ph]').forEach(el => {
            if (el._origPh) el.setAttribute('placeholder', el._origPh);
        });
        return;
    }

    const elements = Array.from(document.querySelectorAll('[data-key]'));
    // Lancer TOUTES les traductions en parallèle
    const promises = elements.map(el => {
        const original = _originals[el.dataset.key] || el.innerText.trim();
        return _translateOne(original, lang);
    });
    const results = await Promise.all(promises);
    elements.forEach((el, i) => { el.innerText = results[i]; });

    // Traduire aussi les placeholders
    const phEls = Array.from(document.querySelectorAll('[data-ph]'));
    const phPromises = phEls.map(el => {
        if (!el._origPh) el._origPh = el.getAttribute('placeholder');
        return _translateOne(el._origPh, lang);
    });
    const phResults = await Promise.all(phPromises);
    phEls.forEach((el, i) => { el.setAttribute('placeholder', phResults[i]); });
}

// ── Ouvrir / fermer le menu ──────────────────────────────────
function toggleLang() {
    const dd  = document.getElementById('lang-dropdown');
    const ico = document.getElementById('lang-chevron');
    if (!dd) return;
    const open = dd.classList.toggle('open');
    if (ico) ico.classList.toggle('open', open);
}

// ── Changer de langue ────────────────────────────────────────
async function selectLang(lang, code, el) {
    if (lang === _currentLang) { toggleLang(); return; }
    _currentLang = lang;
    localStorage.setItem('ts_lang', lang);

    const cur = document.getElementById('current-lang');
    if (cur) cur.innerText = code;
    document.querySelectorAll('.lang-opt').forEach(o => o.classList.remove('active'));
    if (el) el.classList.add('active');
    toggleLang();

    await _translatePage(lang);
}

// ── Fermer si clic en dehors ─────────────────────────────────
document.addEventListener('click', e => {
    if (!e.target.closest('.lang-wrapper')) {
        const dd  = document.getElementById('lang-dropdown');
        const ico = document.getElementById('lang-chevron');
        if (dd)  dd.classList.remove('open');
        if (ico) ico.classList.remove('open');
    }
});

// ── Init au chargement ───────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    _saveOriginals();
    // Sauvegarder les placeholders originaux
    document.querySelectorAll('[data-ph]').forEach(el => {
        el._origPh = el.getAttribute('placeholder');
    });

    if (_currentLang && _currentLang !== SOURCE_LANG) {
        const cur = document.getElementById('current-lang');
        if (cur) {
            const codes = { en: 'EN', ar: 'AR', fr: 'FR' };
            cur.innerText = codes[_currentLang] || 'FR';
        }
        document.querySelectorAll('.lang-opt').forEach(o => {
            o.classList.remove('active');
            if (o.getAttribute('onclick') && o.getAttribute('onclick').includes("'" + _currentLang + "'")) {
                o.classList.add('active');
            }
        });
        _translatePage(_currentLang);
    }
});

// Скриншоты «Гайда по сайту» (README.md): node shoot.js shots.json outDir [BITRIX_PHPSESSID]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const [,, listFile, outDir, sid] = process.argv;
const list = JSON.parse(fs.readFileSync(listFile, 'utf8'));
const BASE = 'http://formaro.localhost';
(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: true, args: ['--hide-scrollbars'],
  });
  fs.mkdirSync(outDir, { recursive: true });
  for (const s of list) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    const mobile = !!s.mobile;
    await page.setViewport({ width: mobile ? 390 : (s.width || 1440), height: s.height || (mobile ? 844 : 900), deviceScaleFactor: mobile ? 2 : 1, isMobile: mobile, hasTouch: mobile });
    if (s.auth && sid) await page.setCookie({ name: 'BITRIX_PHPSESSID', value: sid, domain: 'formaro.localhost', path: '/' });
    await page.setCookie({ name: 'cookie-policy', value: 'Y', domain: 'formaro.localhost', path: '/' });
    if (s.storage) await page.evaluateOnNewDocument(st => { for (const k in st) localStorage.setItem(k, st[k]); }, s.storage);
    try {
      await page.goto(BASE + s.url, { waitUntil: 'networkidle2', timeout: 45000 });
      await page.addStyleTag({ content: '#bx-panel,#panel,.bx-panel-fixed,#bx-panel-back{display:none!important} body{padding-top:0!important}' + (s.css || '') });
      for (const a of (s.actions || [])) {
        if (a.click) { await page.click(a.click); }
        if (a.follow) { const href = await page.$eval(a.follow, el => el.href); await page.goto(href, { waitUntil: 'networkidle2' }); await page.addStyleTag({ content: '#bx-panel{display:none!important}' + (s.css || '') }); }
        if (a.scrollText) { await page.evaluate((t, off) => { const el = [...document.querySelectorAll('h1,h2,h3,h4,.h1,.h2,.h3,div,span,strong,legend,label')].find(e => e.children.length === 0 && e.textContent.trim().startsWith(t)); if (el) window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - off); }, a.scrollText, a.offset || 0); }
        if (a.scroll) { await page.$eval(a.scroll, (el, off) => { window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - off); }, a.offset || 0); }
        if (a.eval) { await page.evaluate(a.eval); }
        if (a.type) { await page.type(a.type[0], a.type[1]); }
        if (a.hover) { await page.hover(a.hover); }
        await new Promise(r => setTimeout(r, a.wait || 600));
      }
      await new Promise(r => setTimeout(r, s.wait || 800));
      if (s.auth) await page.evaluate(() => {
        // Личные данные владельца на скриншотах — демо-значения.
        const map = [[/antyxweb@[a-z.]+/gi, 'client@example.test'], [/\+?7[\s(-]*900[\s)-]*111[\s-]*22[\s-]*33/g, '+7 900 000-00-00']];
        const fix = v => map.reduce((a, [re, to]) => a.replace(re, to), v);
        document.querySelectorAll('input,textarea').forEach(el => { if (el.value) el.value = fix(el.value); });
        const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        while (w.nextNode()) { const n = w.currentNode; const v = fix(n.nodeValue); if (v !== n.nodeValue) n.nodeValue = v; }
      });
      const file = `${outDir}/${s.name}.png`;
      if (s.rect) {
        await page.screenshot({ path: file, clip: s.rect });
      } else if (s.clip) {
        const el = await page.$(s.clip);
        if (!el) throw new Error('no clip ' + s.clip);
        await el.screenshot({ path: file });
      } else {
        await page.screenshot({ path: file, fullPage: !!s.full });
      }
      console.log('ok', s.name);
    } catch (e) { console.log('FAIL', s.name, e.message); }
    await ctx.close();
  }
  await browser.close();
})();

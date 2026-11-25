const puppeteer = require('C:/Users/hp/AppData/Local/Temp/claude/9b9c8e4b7a01c1ab/161eecbc-394b-4689-a973-794198a19757/scratchpad/shots/node_modules/puppeteer-core');
const path = require('path');
(async () => {
  const b = await puppeteer.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files']});
  const jobs = [
    ['report.html', '../ShopNest_Cloud_Report.pdf', 'ShopNest Cloud Report - [STUDENT NAME(S)] [ENROLLMENT NO.(S)]'],
    ['sla.html', '../ShopNest_SLA.pdf', 'ShopNest SLA - [STUDENT NAME(S)] [ENROLLMENT NO.(S)]'],
  ];
  for (const [src, out, footer] of jobs) {
    const p = await b.newPage();
    await p.goto('file:///' + path.resolve(__dirname, src).replace(/\\/g, '/'), {waitUntil: 'networkidle0'});
    await p.pdf({
      path: path.resolve(__dirname, out), format: 'A4', printBackground: true, displayHeaderFooter: true,
      headerTemplate: '<span></span>',
      footerTemplate: `<div style="font-size:8px;width:100%;text-align:center;color:#555">${footer} &nbsp;|&nbsp; Page <span class="pageNumber"></span> / <span class="totalPages"></span></div>`,
      margin: {top: '18mm', bottom: '20mm', left: '16mm', right: '16mm'},
    });
    console.log('pdf', out);
  }
  await b.close();
})();

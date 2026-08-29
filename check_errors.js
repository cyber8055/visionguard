const puppeteer = require('puppeteer');

(async () => {
    try {
        const browser = await puppeteer.launch();
        const page = await browser.newPage();
        
        page.on('console', msg => console.log('PAGE LOG:', msg.text()));
        page.on('pageerror', err => console.log('PAGE ERROR:', err.toString()));
        page.on('requestfailed', req => {
            console.log('REQUEST FAILED:', req.url(), req.failure().errorText);
        });

        await page.goto('file://C:/Users/DELL/Desktop/sentinal/vision-guard/html/dashboard-admin.html', {waitUntil: 'networkidle2'});
        
        await browser.close();
    } catch (e) {
        console.error(e);
    }
})();

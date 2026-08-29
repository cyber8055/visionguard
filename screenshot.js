const puppeteer = require('puppeteer');

(async () => {
    try {
        const browser = await puppeteer.launch();
        const page = await browser.newPage();
        
        await page.setViewport({ width: 1280, height: 800 });
        
        await page.goto('file://C:/Users/DELL/Desktop/sentinal/vision-guard/html/dashboard-admin.html', {waitUntil: 'networkidle2'});
        
        await page.screenshot({path: 'admin_screenshot.png'});
        console.log("Screenshot saved to admin_screenshot.png");
        
        await browser.close();
    } catch (e) {
        console.error(e);
    }
})();

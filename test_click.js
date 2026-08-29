const puppeteer = require('puppeteer');

(async () => {
    try {
        const browser = await puppeteer.launch();
        const page = await browser.newPage();
        
        page.on('console', msg => console.log('PAGE LOG:', msg.text()));
        page.on('pageerror', err => console.log('PAGE ERROR:', err.toString()));
        
        await page.goto('file://C:/Users/DELL/Desktop/sentinal/vision-guard/html/dashboard-admin.html', {waitUntil: 'networkidle2'});
        
        // Wait and click
        try {
            await page.click('a[title="Global Plant Configuration"]');
            console.log("Clicked Plant Config");
            // Check if devAuthModal is visible
            const isVisible = await page.evaluate(() => {
                const modal = document.getElementById('devAuthModal');
                return modal && window.getComputedStyle(modal).display !== 'none';
            });
            console.log("devAuthModal visible:", isVisible);
        } catch(e) {
            console.log("Click Error:", e.message);
        }
        
        await browser.close();
    } catch (e) {
        console.error(e);
    }
})();

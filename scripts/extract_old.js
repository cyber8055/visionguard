const fs = require('fs');
const path = 'C:\\Users\\DELL\\.gemini\\antigravity-ide\\brain\\1106ae31-0beb-4abd-b48a-2c4d1f09f903\\.system_generated\\logs\\transcript_full.jsonl';
const lines = fs.readFileSync(path, 'utf8').split('\n');
let fileContent = '';

for (const line of lines) {
    if (!line) continue;
    try {
        const obj = JSON.parse(line);
        if (obj.type === 'TOOL_RESPONSE' && obj.content && obj.content.includes('auth-login-basic.html')) {
            if (obj.content.includes('File Path: `file:///c:/Users/DELL/Desktop/sentinal/vision-guard/html/auth-login-basic.html`')) {
                // Extract content
                console.log('Found a view_file response!');
                const content = obj.content;
                // Parse it out
                const startStr = 'The following code has been modified to include a line number before every line';
                const endStr = 'The above content shows the entire, complete file contents';
                
                let startIdx = content.indexOf(startStr);
                let endIdx = content.lastIndexOf(endStr);
                
                if (startIdx !== -1 && endIdx !== -1) {
                    startIdx = content.indexOf('\n', startIdx) + 1;
                    let rawContent = content.substring(startIdx, endIdx);
                    // Remove line numbers
                    rawContent = rawContent.split('\n').map(l => {
                        const colonIdx = l.indexOf(': ');
                        return colonIdx !== -1 ? l.substring(colonIdx + 2) : l;
                    }).join('\n');
                    
                    fs.writeFileSync('C:\\Users\\DELL\\Desktop\\sentinal\\vision-guard\\html\\auth-login-basic-reverted.html', rawContent);
                    console.log('Saved to auth-login-basic-reverted.html');
                    break;
                }
            }
        }
    } catch (e) {
        console.error(e);
    }
}

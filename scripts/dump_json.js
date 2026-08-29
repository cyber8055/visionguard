const fs = require('fs');

const logPath = 'C:\\Users\\DELL\\.gemini\\antigravity-ide\\brain\\1106ae31-0beb-4abd-b48a-2c4d1f09f903\\.system_generated\\logs\\transcript_full.jsonl';

const lines = fs.readFileSync(logPath, 'utf8').split('\n');
for (let i = lines.length - 1; i >= 0; i--) {
  if (!lines[i]) continue;
  try {
    const obj = JSON.parse(lines[i]);
    if (obj.type === 'USER_INPUT' && obj.content && obj.content.includes('"format": "VisionGuard exact UI/source snapshot"')) {
       fs.writeFileSync('C:\\Users\\DELL\\Desktop\\sentinal\\vision-guard\\found_json.txt', obj.content);
       console.log('Found and wrote to found_json.txt');
       break;
    }
  } catch (e) {}
}

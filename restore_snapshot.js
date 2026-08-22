const fs = require('fs');
const path = require('path');

const jsonPath = 'C:\\Users\\DELL\\.gemini\\antigravity-ide\\brain\\1106ae31-0beb-4abd-b48a-2c4d1f09f903\\.user_uploaded\\media_1787396945848.json';
const targetDir = 'C:\\Users\\DELL\\Desktop\\sentinal\\vision-guard';

try {
  const content = fs.readFileSync(jsonPath, 'utf8');
  const snapshotJson = JSON.parse(content);

  if (snapshotJson && snapshotJson.source_files) {
    let count = 0;
    for (const [filePath, fileData] of Object.entries(snapshotJson.source_files)) {
      const fullPath = path.join(targetDir, filePath);
      const dir = path.dirname(fullPath);
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
      }
      fs.writeFileSync(fullPath, fileData.content || fileData);
      count++;
      console.log('Restored:', filePath);
    }
    console.log(`Successfully restored ${count} files.`);
  } else {
    console.log('source_files not found in the JSON.');
  }
} catch (e) {
  console.error('Error:', e);
}

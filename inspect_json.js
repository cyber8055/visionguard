const fs = require('fs');
const content = fs.readFileSync('found_json.txt', 'utf8');
const startIdx = content.indexOf('{');
const endIdx = content.lastIndexOf('}');
const jsonStr = content.substring(startIdx, endIdx + 1);
try {
  const data = JSON.parse(jsonStr);
  console.log('Keys:', Object.keys(data));
  if (data.source_files) {
    console.log('source_files keys:', Object.keys(data.source_files));
  }
  if (data.files) {
    console.log('files keys:', Object.keys(data.files));
  }
} catch (e) {
  console.error('JSON parse error:', e);
}

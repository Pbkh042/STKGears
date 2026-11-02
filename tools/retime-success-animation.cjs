// Change GIF frame timing only; preserve the original artwork and compressed frames.
const fs = require('node:fs');
const path = require('node:path');
const input = path.join(__dirname, '../assets/animations/success-confetti.gif');
const output = path.join(__dirname, '../assets/animations/success-confetti-fast.gif');
const gif = Buffer.from(fs.readFileSync(input));
if (!/^GIF8[79]a$/.test(gif.toString('ascii', 0, 6))) throw Error('Invalid GIF');
let cursor = 13;
if (gif[10] & 0x80) cursor += 3 * (1 << ((gif[10] & 7) + 1));
let frames = 0, oldDuration = 0, newDuration = 0;
function skipBlocks() {
  while (cursor < gif.length) {
    const size = gif[cursor++];
    if (size === 0) return;
    cursor += size;
  }
  throw Error('Truncated GIF');
}
while (cursor < gif.length) {
  const marker = gif[cursor++];
  if (marker === 0x3b) break;
  if (marker === 0x21) {
    const label = gif[cursor++];
    if (label === 0xf9) {
      if (gif[cursor] !== 4) throw Error('Invalid frame control');
      const delay = gif.readUInt16LE(cursor + 2);
      const faster = Math.max(2, Math.round(delay / 2));
      gif.writeUInt16LE(faster, cursor + 2);
      frames++;
      oldDuration += delay * 10;
      newDuration += faster * 10;
    }
    skipBlocks();
  } else if (marker === 0x2c) {
    const packed = gif[cursor + 8];
    cursor += 9;
    if (packed & 0x80) cursor += 3 * (1 << ((packed & 7) + 1));
    cursor++; // LZW minimum code size
    skipBlocks();
  } else {
    throw Error('Unknown GIF block: ' + marker);
  }
}
if (!frames) throw Error('No frames found');
fs.writeFileSync(output, gif);
console.log(JSON.stringify({ frames, oldDuration, newDuration }));

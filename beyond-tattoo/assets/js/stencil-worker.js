'use strict';

self.onmessage = event => {
  const {job, mode, bitmap} = event.data || {};
  try {
    const ratio = Math.min(1, 1400 / Math.max(bitmap.width, bitmap.height));
    const width = Math.max(1, Math.round(bitmap.width * ratio));
    const height = Math.max(1, Math.round(bitmap.height * ratio));
    const canvas = new OffscreenCanvas(width, height);
    const context = canvas.getContext('2d', {willReadFrequently: true});
    context.fillStyle = '#fff';
    context.fillRect(0, 0, width, height);
    context.drawImage(bitmap, 0, 0, width, height);
    bitmap.close();

    const pixels = context.getImageData(0, 0, width, height);
    const source = pixels.data;
    const count = width * height;
    const gray = new Uint8Array(count);
    for (let pixel = 0, index = 0; index < count; pixel += 4, index++) {
      gray[index] = source[pixel] * 0.299 + source[pixel + 1] * 0.587 + source[pixel + 2] * 0.114;
    }

    const output = context.createImageData(width, height);
    if (mode === 'standard') {
      for (let index = 0; index < count; index++) {
        const value = gray[index];
        const shade = value < 52 ? 0 : value < 112 ? 78 : value < 178 ? 168 : 255;
        const pixel = index * 4;
        output.data[pixel] = output.data[pixel + 1] = output.data[pixel + 2] = shade;
        output.data[pixel + 3] = 255;
      }
    } else {
      for (let y = 1; y < height - 1; y++) {
        for (let x = 1; x < width - 1; x++) {
          const index = y * width + x;
          const gx = -gray[index - width - 1] + gray[index - width + 1] - 2 * gray[index - 1] + 2 * gray[index + 1] - gray[index + width - 1] + gray[index + width + 1];
          const gy = -gray[index - width - 1] - 2 * gray[index - width] - gray[index - width + 1] + gray[index + width - 1] + 2 * gray[index + width] + gray[index + width + 1];
          const edge = Math.hypot(gx, gy);
          const shade = gray[index];
          const ink = mode === 'outline'
            ? edge > 92
            : edge > 78 || (shade < 145 && ((x + y) % 9 < 2 || (x - y + 10000) % 12 < 2));
          const pixel = index * 4;
          output.data[pixel] = output.data[pixel + 1] = output.data[pixel + 2] = ink ? 0 : 255;
          output.data[pixel + 3] = 255;
        }
      }
      for (let x = 0; x < width; x++) {
        output.data[x * 4] = output.data[x * 4 + 1] = output.data[x * 4 + 2] = 255;
        output.data[x * 4 + 3] = 255;
        const last = ((height - 1) * width + x) * 4;
        output.data[last] = output.data[last + 1] = output.data[last + 2] = 255;
        output.data[last + 3] = 255;
      }
      for (let y = 0; y < height; y++) {
        const first = (y * width) * 4;
        output.data[first] = output.data[first + 1] = output.data[first + 2] = 255;
        const final = (y * width + width - 1) * 4;
        output.data[final] = output.data[final + 1] = output.data[final + 2] = 255;
        output.data[(y * width) * 4 + 3] = 255;
        output.data[(y * width + width - 1) * 4 + 3] = 255;
      }
    }
    context.putImageData(output, 0, 0);
    const resultBitmap = canvas.transferToImageBitmap();
    self.postMessage({job, bitmap: resultBitmap}, [resultBitmap]);
  } catch (error) {
    bitmap?.close?.();
    self.postMessage({job, error: 'render_failed'});
  }
};

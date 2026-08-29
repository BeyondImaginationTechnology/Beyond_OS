export const LANDSCAPE_CANVAS_SELECTOR = '[class*="aspect-[16/9]"]';
export const PORTRAIT_CANVAS_SELECTOR = '[class*="aspect-[9/16]"]';

export const inferHtmlRenderOptions = (html, requested) => {
  const options = {...requested};
  const selector = String(options.selector || '').trim();
  const hasPortraitCanvas = html.includes('aspect-[9/16]');
  const hasLandscapeCanvas = html.includes('aspect-[16/9]') || html.includes('aspect-video');
  const usingLandscapeDefault = selector === '' || selector === LANDSCAPE_CANVAS_SELECTOR;

  // The Studio opens in 16:9, but many bundled React artifacts contain a
  // single phone/Reel canvas. Rendering the default selector in that case
  // misses the canvas and captures the surrounding editor instead.
  if (hasPortraitCanvas && !hasLandscapeCanvas && usingLandscapeDefault) {
    const shortEdge = Math.min(options.width, options.height);
    const longEdge = Math.max(options.width, options.height);
    options.width = shortEdge;
    options.height = longEdge;
    options.selector = PORTRAIT_CANVAS_SELECTOR;
    options.detectedRatio = '9:16';
  }

  return options;
};

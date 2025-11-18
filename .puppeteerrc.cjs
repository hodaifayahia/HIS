/**
 * @type {import("puppeteer").Configuration}
 */
module.exports = {
  // Point to the cache directory
  cacheDirectory: '/home/sail/.cache/puppeteer',
  // Chrome executable path
  executablePath: process.env.CHROME_PATH || '/home/administrator/.cache/puppeteer/chrome-headless-shell/linux-141.0.7390.78/chrome-headless-shell-linux64/chrome-headless-shell',
  // Skip download if already installed
  skipDownload: true,
};

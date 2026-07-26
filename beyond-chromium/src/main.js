const path = require("node:path");
const { app, BrowserWindow, WebContentsView, ipcMain } = require("electron");

const TOOLBAR_HEIGHT = 76;
const START_URL = "https://beyondimagination.co.technology/";

let window;
let page;
let pointer = { x: 640, y: 420 };
let keyboardOpen = false;

function installControllerCursor() {
  if (!page || page.webContents.isDestroyed()) return;
  page.webContents
    .executeJavaScript(`
      (() => {
        let dot = document.getElementById("__beyond_controller_cursor");
        if (!dot) {
          dot = document.createElement("div");
          dot.id = "__beyond_controller_cursor";
          Object.assign(dot.style, {
            position: "fixed", zIndex: "2147483647", width: "22px",
            height: "22px", border: "3px solid white", borderRadius: "50%",
            background: "#7c5cff99", boxShadow: "0 0 0 3px #0008, 0 0 18px #7c5cff",
            pointerEvents: "none", transform: "translate(-50%, -50%)"
          });
          document.documentElement.appendChild(dot);
        }
        dot.style.left = ${Math.round(pointer.x)} + "px";
        dot.style.top = ${Math.round(pointer.y - TOOLBAR_HEIGHT)} + "px";
      })()
    `)
    .catch(() => {});
}

function normalizeUrl(value) {
  const input = String(value || "").trim();
  if (!input) return START_URL;
  if (/^https?:\/\//i.test(input)) return input;
  if (input.includes(".") && !input.includes(" ")) return `https://${input}`;
  return `https://www.google.com/search?q=${encodeURIComponent(input)}`;
}

function sendState() {
  if (!window || window.isDestroyed() || !page) return;
  window.webContents.send("browser-state", {
    url: page.webContents.getURL(),
    title: page.webContents.getTitle() || "Beyond Chromium",
    canGoBack: page.webContents.canGoBack(),
    canGoForward: page.webContents.canGoForward(),
  });
}

function sizePage() {
  if (!window || !page) return;
  const [width, height] = window.getContentSize();
  page.setBounds({
    x: 0,
    y: TOOLBAR_HEIGHT,
    width,
    height: Math.max(0, height - TOOLBAR_HEIGHT - (keyboardOpen ? 330 : 0)),
  });
}

function createWindow() {
  window = new BrowserWindow({
    width: 1440,
    height: 900,
    minWidth: 900,
    minHeight: 600,
    backgroundColor: "#080b14",
    title: "Beyond Chromium",
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, "preload.js"),
      contextIsolation: true,
      nodeIntegration: false,
    },
  });

  page = new WebContentsView({
    webPreferences: {
      contextIsolation: true,
      sandbox: true,
    },
  });
  window.contentView.addChildView(page);
  sizePage();

  window.loadFile(path.join(__dirname, "index.html"));
  page.webContents.loadURL(START_URL);

  window.on("resize", sizePage);
  page.webContents.on("did-navigate", sendState);
  page.webContents.on("did-navigate-in-page", sendState);
  page.webContents.on("did-finish-load", installControllerCursor);
  page.webContents.on("page-title-updated", sendState);
  page.webContents.setWindowOpenHandler(({ url }) => {
    page.webContents.loadURL(url);
    return { action: "deny" };
  });
}

ipcMain.on("navigate", (_event, value) => {
  page?.webContents.loadURL(normalizeUrl(value));
});

ipcMain.on("browser-action", (_event, action, payload = {}) => {
  if (!page) return;
  const contents = page.webContents;
  const [width, height] = window.getContentSize();

  if (action === "move") {
    pointer.x = Math.max(0, Math.min(width - 1, pointer.x + (payload.x || 0)));
    pointer.y = Math.max(
      TOOLBAR_HEIGHT,
      Math.min(height - 1, pointer.y + (payload.y || 0))
    );
    contents.sendInputEvent({
      type: "mouseMove",
      x: Math.round(pointer.x),
      y: Math.round(pointer.y - TOOLBAR_HEIGHT),
      movementX: Math.round(payload.x || 0),
      movementY: Math.round(payload.y || 0),
    });
    installControllerCursor();
  } else if (action === "click") {
    const event = {
      x: Math.round(pointer.x),
      y: Math.round(pointer.y - TOOLBAR_HEIGHT),
      button: "left",
      clickCount: 1,
    };
    contents.sendInputEvent({ type: "mouseDown", ...event });
    contents.sendInputEvent({ type: "mouseUp", ...event });
  } else if (action === "scroll") {
    contents.sendInputEvent({
      type: "mouseWheel",
      x: Math.round(pointer.x),
      y: Math.round(pointer.y - TOOLBAR_HEIGHT),
      deltaX: payload.x || 0,
      deltaY: payload.y || 0,
      canScroll: true,
    });
  } else if (action === "back" && contents.canGoBack()) {
    contents.goBack();
  } else if (action === "forward" && contents.canGoForward()) {
    contents.goForward();
  } else if (action === "reload") {
    contents.reload();
  } else if (action === "text" && payload.text) {
    contents.insertText(payload.text);
  } else if (action === "key") {
    contents.sendInputEvent({ type: "keyDown", keyCode: payload.key });
    contents.sendInputEvent({ type: "keyUp", keyCode: payload.key });
  } else if (action === "keyboard") {
    keyboardOpen = Boolean(payload.open);
    sizePage();
  }
});

app.whenReady().then(createWindow);
app.on("window-all-closed", () => {
  if (process.platform !== "darwin") app.quit();
});
app.on("activate", () => {
  if (BrowserWindow.getAllWindows().length === 0) createWindow();
});

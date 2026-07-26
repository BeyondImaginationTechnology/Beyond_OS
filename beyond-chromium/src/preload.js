const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("beyond", {
  navigate: (url) => ipcRenderer.send("navigate", url),
  browserAction: (action, payload) =>
    ipcRenderer.send("browser-action", action, payload),
  onState: (callback) =>
    ipcRenderer.on("browser-state", (_event, state) => callback(state)),
});

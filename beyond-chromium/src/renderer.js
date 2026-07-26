const address = document.querySelector("#address");
const keyboard = document.querySelector("#keyboard");
const keysRoot = document.querySelector("#keys");
const status = document.querySelector("#controller-status");

const keyValues = [
  "1","2","3","4","5","6","7","8","9","0",
  "q","w","e","r","t","y","u","i","o","p",
  "a","s","d","f","g","h","j","k","l",".",
  "z","x","c","v","b","n","m","-","_","/",
  ".com","Space","Backspace","Enter"
];

let cursorPosition = { x: innerWidth / 2, y: innerHeight / 2 };
let selectedKey = 0;
let previousButtons = [];
let lastFrame = performance.now();

function setControllerStatus(label, connected) {
  status.replaceChildren();
  status.append(document.createElement("span"), document.createTextNode(label));
  status.classList.toggle("connected", connected);
}

function renderKeyboard() {
  keysRoot.innerHTML = "";
  keyValues.forEach((value, index) => {
    const button = document.createElement("button");
    button.className = `key ${index === selectedKey ? "selected" : ""}`;
    if (["Space", "Backspace", "Enter"].includes(value)) button.classList.add("wide");
    button.textContent = value;
    button.addEventListener("click", () => typeKey(value));
    keysRoot.append(button);
  });
}

function typeKey(value) {
  if (value === "Space") window.beyond.browserAction("text", { text: " " });
  else if (value === "Backspace") window.beyond.browserAction("key", { key: "Backspace" });
  else if (value === "Enter") window.beyond.browserAction("key", { key: "Enter" });
  else window.beyond.browserAction("text", { text: value });
}

function toggleKeyboard(force) {
  keyboard.classList.toggle("hidden", force ?? !keyboard.classList.contains("hidden"));
  window.beyond.browserAction("keyboard", {
    open: !keyboard.classList.contains("hidden"),
  });
}

document.querySelector("#address-form").addEventListener("submit", (event) => {
  event.preventDefault();
  window.beyond.navigate(address.value);
  address.blur();
});
document.querySelector("#back").onclick = () => window.beyond.browserAction("back");
document.querySelector("#forward").onclick = () => window.beyond.browserAction("forward");
document.querySelector("#reload").onclick = () => window.beyond.browserAction("reload");
document.querySelector("#keyboard-button").onclick = () => toggleKeyboard();

window.beyond.onState((state) => {
  if (document.activeElement !== address) address.value = state.url;
  document.querySelector("#back").disabled = !state.canGoBack;
  document.querySelector("#forward").disabled = !state.canGoForward;
  document.title = `${state.title} — Beyond Chromium`;
});

function pressed(gamepad, index) {
  return gamepad.buttons[index]?.pressed && !previousButtons[index];
}

function gamepadLoop(now) {
  const gamepad = [...navigator.getGamepads()].find(Boolean);
  const delta = Math.min(2, (now - lastFrame) / 16.67);
  lastFrame = now;

  if (gamepad) {
    setControllerStatus(`Controller: ${gamepad.id.split("(")[0].trim()}`, true);

    const deadzone = (value) => Math.abs(value || 0) > 0.16 ? value : 0;
    const dx = deadzone(gamepad.axes[0]) * 12 * delta;
    const dy = deadzone(gamepad.axes[1]) * 12 * delta;
    const sx = deadzone(gamepad.axes[2]) * 18 * delta;
    const sy = deadzone(gamepad.axes[3]) * 18 * delta;

    if (dx || dy) {
      cursorPosition.x = Math.max(0, Math.min(innerWidth, cursorPosition.x + dx));
      cursorPosition.y = Math.max(76, Math.min(innerHeight, cursorPosition.y + dy));
      window.beyond.browserAction("move", { x: dx, y: dy });
    }
    if (sx || sy) window.beyond.browserAction("scroll", { x: sx, y: sy });

    if (pressed(gamepad, 0)) {
      if (keyboard.classList.contains("hidden")) window.beyond.browserAction("click");
      else typeKey(keyValues[selectedKey]);
    }
    if (pressed(gamepad, 1)) {
      if (!keyboard.classList.contains("hidden")) toggleKeyboard(false);
      else window.beyond.browserAction("back");
    }
    if (pressed(gamepad, 2)) toggleKeyboard();
    if (pressed(gamepad, 3)) address.focus();
    if (pressed(gamepad, 9)) window.beyond.browserAction("reload");

    if (!keyboard.classList.contains("hidden")) {
      let next = selectedKey;
      if (pressed(gamepad, 12)) next -= 10;
      if (pressed(gamepad, 13)) next += 10;
      if (pressed(gamepad, 14)) next -= 1;
      if (pressed(gamepad, 15)) next += 1;
      selectedKey = Math.max(0, Math.min(keyValues.length - 1, next));
      if (next !== selectedKey || [12,13,14,15].some((i) => pressed(gamepad, i))) {
        renderKeyboard();
      }
    }

    previousButtons = gamepad.buttons.map((button) => button.pressed);
  } else {
    setControllerStatus("Controller: waiting", false);
    previousButtons = [];
  }
  requestAnimationFrame(gamepadLoop);
}

renderKeyboard();
requestAnimationFrame(gamepadLoop);

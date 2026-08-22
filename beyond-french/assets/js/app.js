document.querySelectorAll(".copy-phrase").forEach(function (button) {
  button.addEventListener("click", async function () {
    try {
      await navigator.clipboard.writeText(button.dataset.copy || "");
      button.textContent = "Copied";
      setTimeout(function () {
        button.textContent = "Copy French";
      }, 1600);
    } catch (error) {
      button.textContent = "Copy failed";
    }
  });
});

(function installBeyondFrenchWebApp() {
  const button = document.querySelector("#install-beyond-french");
  if (!button || window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true) return;
  let installPrompt = null;
  window.addEventListener("beforeinstallprompt", function (event) {
    event.preventDefault();
    installPrompt = event;
    button.hidden = false;
    button.classList.add("ready");
  });
  button.addEventListener("click", async function () {
    if (!installPrompt) return;
    button.disabled = true;
    await installPrompt.prompt();
    const choice = await installPrompt.userChoice;
    installPrompt = null;
    button.disabled = false;
    if (choice.outcome === "accepted") button.hidden = true;
  });
  window.addEventListener("appinstalled", function () { button.hidden = true; });
})();

class BeyondLocalVoiceAPI {
  constructor() {
    this.synth = window.speechSynthesis;
    this.voices = [];
    this.activeLocale = "fr-FR";
    this.select = document.querySelector("#local-voice-select");
    this.status = document.querySelector(".voice-status");
    this.rate = document.querySelector("#voice-rate");
    this.rateOutput = document.querySelector("#voice-rate-output");
    this.cards = Array.from(document.querySelectorAll(".voice-card"));
    this.audio = null;
    this.audioObjectUrl = "";
    this.init();
  }

  init() {
    if (this.synth) {
      this.loadVoices();
      this.synth.addEventListener?.("voiceschanged", () => this.loadVoices());
      setTimeout(() => this.loadVoices(), 300);
    }

    this.cards.forEach((card) => {
      card.setAttribute("aria-pressed", card.classList.contains("active") ? "true" : "false");
      card.addEventListener("click", () => this.playCard(card));
    });

    document.querySelector(".voice-stop")?.addEventListener("click", () => this.stop());
    this.rate?.addEventListener("input", () => {
      this.rateOutput.textContent = Number(this.rate.value).toFixed(2) + "x";
    });
    this.select?.addEventListener("change", () => {
      const voice = this.voices[Number(this.select.value)];
      if (voice) this.setStatus("Selected " + voice.name + " · " + voice.lang + (voice.localService ? " · local" : ""));
    });
  }

  loadVoices() {
    this.voices = (this.synth?.getVoices() || []).sort(
      (a, b) => Number(b.localService) - Number(a.localService) || a.lang.localeCompare(b.lang) || a.name.localeCompare(b.name),
    );
    this.refreshSelect(this.activeLocale);
  }

  matching(locale) {
    const exact = this.voices.filter((voice) => voice.lang.toLowerCase() === locale.toLowerCase());
    const prefix = locale.split("-")[0].toLowerCase();
    const language = this.voices.filter((voice) => voice.lang.toLowerCase().startsWith(prefix));
    return exact.concat(language).filter((voice, index, all) => all.indexOf(voice) === index).sort((a, b) => Number(b.localService) - Number(a.localService));
  }

  refreshSelect(locale) {
    if (!this.select) return;
    const matches = this.matching(locale);
    this.select.innerHTML = "";
    if (matches.length) {
      matches.forEach((voice) => {
        const index = this.voices.indexOf(voice);
        this.select.add(new Option(voice.name + " - " + voice.lang + (voice.localService ? " · On device" : ""), String(index)));
      });
    } else {
      this.select.add(new Option("Protected API fallback", ""));
    }
  }

  setSelectedCard(card) {
    this.cards.forEach((item) => {
      const selected = item === card;
      item.classList.toggle("active", selected);
      item.setAttribute("aria-pressed", selected ? "true" : "false");
    });
  }

  setPlayingCard(card) {
    this.cards.forEach((item) => item.classList.toggle("speaking", item === card));
  }

  clearPlayingCard() {
    this.cards.forEach((card) => card.classList.remove("speaking"));
  }

  playCard(card) {
    this.setSelectedCard(card);
    this.activeLocale = card.dataset.locale || "fr-FR";
    this.refreshSelect(this.activeLocale);
    if (card.dataset.audioUrl) {
      this.playPublished(card.dataset.audioUrl, card);
    } else {
      this.speak(card.dataset.speak || "", this.activeLocale, card);
    }
  }

  async playPublished(url, card) {
    try {
      this.stop(false);
      this.setPlayingCard(card);
      this.setStatus("Lesson narration is playing.");
      this.audio = new Audio(url);
      this.audio.onended = () => {
        this.clearPlayingCard();
        this.setStatus("Finished. Tap another translation to compare.");
      };
      this.audio.onerror = () => this.speak(card.dataset.speak || "", this.activeLocale, card);
      await this.audio.play();
    } catch (error) {
      this.clearPlayingCard();
      await this.speak(card.dataset.speak || "", this.activeLocale, card);
    }
  }

  async speak(text, locale, card = null) {
    if (!text) return;
    this.stop(false);
    const matches = this.matching(locale);
    const voice = this.select && this.select.value !== "" ? this.voices[Number(this.select.value)] : matches[0];

    if (this.synth && voice) {
      const utterance = new SpeechSynthesisUtterance(text);
      utterance.lang = locale;
      utterance.rate = Number(this.rate?.value || 0.88);
      utterance.voice = voice;
      utterance.onstart = () => {
        this.setPlayingCard(card);
        this.setStatus("Lesson narration is playing.");
      };
      utterance.onend = () => {
        this.clearPlayingCard();
        this.setStatus("Finished. Tap another translation to compare.");
      };
      utterance.onerror = () => this.playApi(text, locale, card);
      this.synth.speak(utterance);
      return;
    }

    await this.playApi(text, locale, card);
  }

  async playApi(text, locale, card) {
    try {
      this.setPlayingCard(card);
      this.setStatus("Loading protected " + (card?.dataset.language || locale) + " voice...");
      const response = await fetch("api/voice.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ text, locale }),
      });
      if (!response.ok) throw new Error("unavailable");
      const blob = await response.blob();
      if (this.audioObjectUrl) URL.revokeObjectURL(this.audioObjectUrl);
      this.audioObjectUrl = URL.createObjectURL(blob);
      this.audio = new Audio(this.audioObjectUrl);
      this.audio.playbackRate = Number(this.rate?.value || 0.88);
      this.audio.onended = () => {
        this.clearPlayingCard();
        this.setStatus("Finished. Tap another translation to compare.");
      };
      await this.audio.play();
    } catch (error) {
      this.clearPlayingCard();
      this.setStatus("No compatible voice is configured for this translation yet.");
    }
  }

  stop(announce = true) {
    this.synth?.cancel();
    if (this.audio) {
      this.audio.pause();
      this.audio = null;
    }
    this.clearPlayingCard();
    if (announce) this.setStatus("Audio stopped.");
  }

  setStatus(text) {
    if (this.status) this.status.textContent = text;
  }
}

new BeyondLocalVoiceAPI();

document.querySelectorAll(".speak-phrase").forEach(function (button) {
  button.addEventListener("click", function () {
    document.querySelector('.voice-card[data-locale="' + button.dataset.locale + '"]')?.click();
  });
});

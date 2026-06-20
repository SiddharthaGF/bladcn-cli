/**
 * Register Alpine.data factories on first load.
 */
window.bladcnOnAlpine =
  window.bladcnOnAlpine ??
  ((callback) => {
    if (typeof window.Alpine !== "undefined") {
      callback(window.Alpine);
    }
  });

window.bladcnRegister =
  window.bladcnRegister ??
  ((name, factory) => {
    bladcnOnAlpine((Alpine) => {
      Alpine.data(name, factory);
    });
  });

bladcnRegister("bladcnScrollArea", () => ({}));

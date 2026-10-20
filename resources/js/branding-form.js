// Alpine component for Admin, Settings, Branding (SPEC 11.6). The live preview reads the form values before saving;
// a chosen file is previewed with URL.createObjectURL and nothing is uploaded until Save.

/**
 * @param {{ name: string, tagline: string, email: string, logoLight: string|null, logoDark: string|null, favicon: string|null }} saved
 * @param {{ name?: string, tagline?: string, email?: string }} current values to show after a failed save
 */
export default function brandingForm(saved, current = {}) {
    return {
        saved,
        name: current.name ?? saved.name,
        tagline: current.tagline ?? saved.tagline,
        email: current.email ?? saved.email,
        logoLight: saved.logoLight,
        logoDark: saved.logoDark,
        favicon: saved.favicon,
        removeLight: false,
        removeDark: false,
        removeFavicon: false,
        filesChanged: false,

        get dirty() {
            return this.filesChanged
                || this.removeLight || this.removeDark || this.removeFavicon
                || this.name !== this.saved.name
                || this.tagline !== this.saved.tagline
                || this.email !== this.saved.email;
        },

        get shownName() {
            return this.name.trim() || this.saved.name;
        },

        pick(event, key, removeKey) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            this[key] = URL.createObjectURL(file);
            this[removeKey] = false;
            this.filesChanged = true;
        },

        clear(key, removeKey) {
            this[key] = null;
            this[removeKey] = true;
        },
    };
}

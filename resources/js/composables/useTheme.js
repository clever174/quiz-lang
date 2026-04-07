import { ref, watch } from 'vue';

const THEMES = ['light', 'dark', 'space'];
const stored = typeof localStorage !== 'undefined' ? localStorage.getItem('theme') : null;
const current = ref(THEMES.includes(stored) ? stored : 'light');

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

applyTheme(current.value);
watch(current, applyTheme);

export function useTheme() {
    return { current, themes: THEMES };
}

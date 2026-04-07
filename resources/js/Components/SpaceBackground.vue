<script setup>
import { ref, nextTick, onMounted, onUnmounted, watch } from 'vue';
import { useTheme } from '@/composables/useTheme';

const { current: theme } = useTheme();
const canvasRef = ref(null);
let rafId = null;
let stars = [];

function resize() {
    const c = canvasRef.value;
    if (!c) return;
    c.width = window.innerWidth;
    c.height = window.innerHeight;
}

function initStars() {
    const c = canvasRef.value;
    if (!c) return;
    stars = [];
    const n = Math.floor((c.width * c.height) / 2400);
    for (let i = 0; i < n; i++) {
        stars.push({
            x: Math.random() * c.width,
            y: Math.random() * c.height,
            r: Math.random() * 1.3 + 0.2,
            a: Math.random(),
            da: (Math.random() - 0.5) * 0.004,
            tw: Math.random() > 0.55,
        });
    }
}

function draw() {
    const c = canvasRef.value;
    if (!c) return;
    const ctx = c.getContext('2d');
    ctx.clearRect(0, 0, c.width, c.height);
    for (const s of stars) {
        if (s.tw) {
            s.a += s.da;
            if (s.a < 0.08 || s.a > 1) s.da *= -1;
        }
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(200,210,255,${Math.min(1, Math.max(0, s.a)).toFixed(2)})`;
        ctx.fill();
    }
    rafId = requestAnimationFrame(draw);
}

function start() {
    stop();
    resize();
    initStars();
    draw();
    window.addEventListener('resize', onResize);
}

function stop() {
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    window.removeEventListener('resize', onResize);
    stars = [];
}

function onResize() {
    resize();
    initStars();
}

watch(theme, async (val) => {
    if (val === 'space') {
        await nextTick();
        start();
    } else {
        stop();
    }
});

onMounted(() => {
    if (theme.value === 'space') start();
});

onUnmounted(stop);
</script>

<template>
    <template v-if="theme === 'space'">
        <!-- Starfield canvas -->
        <canvas
            ref="canvasRef"
            class="fixed inset-0 pointer-events-none"
            style="z-index: 0;"
        />
        <!-- Nebula blobs -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden" style="z-index: 0;">
            <div class="space-neb space-neb-1" />
            <div class="space-neb space-neb-2" />
            <div class="space-neb space-neb-3" />
        </div>
    </template>
</template>

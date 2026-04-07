<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ match: Object, mechanic: String });
const page = usePage();

const mechanicOptions = [
    { label: '🔗 Match Up — соединить колонки', value: 'match_up' },
    { label: '🃏 Matching Pairs — найти пары', value: 'matching_pairs' },
    { label: '✅ True or False — правда или ложь', value: 'true_or_false' },
];

function changeMechanic(value) {
    router.get(route('match.show', props.match.id), { mechanic: value }, {
        preserveScroll: false,
        replace: true,
    });
}

// ─── Match Up ────────────────────────────────────────────────────────────────

function imgSrc(path) {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
}

function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

// Each item: { pairId, side('a'|'b'), text, image }
const leftItems  = ref([]);
const rightItems = ref([]);
const selectedLeft  = ref(null); // pairId
const selectedRight = ref(null); // pairId
const connections   = ref([]);   // [{ leftId, rightId, correct }]
const muChecked     = ref(false);

function initMatchUp() {
    const pairs = props.match.pairs;
    leftItems.value  = shuffle(pairs.map(p => ({ pairId: p.id, text: p.item_a, image: p.item_a_image })));
    rightItems.value = shuffle(pairs.map(p => ({ pairId: p.id, text: p.item_b, image: p.item_b_image })));
    connections.value = [];
    selectedLeft.value  = null;
    selectedRight.value = null;
    muChecked.value = false;
}

initMatchUp();

function connectedLeft(pairId) {
    return connections.value.find(c => c.leftId === pairId);
}
function connectedRight(pairId) {
    return connections.value.find(c => c.rightId === pairId);
}

function selectLeft(pairId) {
    if (muChecked.value) return;
    if (connectedLeft(pairId)) {
        // disconnect
        connections.value = connections.value.filter(c => c.leftId !== pairId);
        return;
    }
    selectedLeft.value = pairId;
    tryConnect();
}

function selectRight(pairId) {
    if (muChecked.value) return;
    if (connectedRight(pairId)) {
        connections.value = connections.value.filter(c => c.rightId !== pairId);
        return;
    }
    selectedRight.value = pairId;
    tryConnect();
}

function tryConnect() {
    if (selectedLeft.value === null || selectedRight.value === null) return;
    // Remove existing connections for either side
    connections.value = connections.value.filter(
        c => c.leftId !== selectedLeft.value && c.rightId !== selectedRight.value
    );
    connections.value.push({ leftId: selectedLeft.value, rightId: selectedRight.value });
    selectedLeft.value  = null;
    selectedRight.value = null;
}

function checkMatchUp() {
    muChecked.value = true;
    connections.value = connections.value.map(c => ({
        ...c,
        correct: c.leftId === c.rightId,
    }));
}

const muAllConnected = computed(() => connections.value.length === props.match.pairs.length);
const muScore = computed(() => connections.value.filter(c => c.correct).length);

function leftItemClass(pairId) {
    if (selectedLeft.value === pairId) return 'border-blue-500 bg-blue-50 ring-2 ring-blue-300';
    const conn = connectedLeft(pairId);
    if (!conn) return 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50 cursor-pointer';
    if (!muChecked.value) return 'border-blue-400 bg-blue-50';
    return conn.correct ? 'border-green-500 bg-green-50' : 'border-red-400 bg-red-50';
}

function rightItemClass(pairId) {
    if (selectedRight.value === pairId) return 'border-blue-500 bg-blue-50 ring-2 ring-blue-300';
    const conn = connectedRight(pairId);
    if (!conn) return 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50 cursor-pointer';
    if (!muChecked.value) return 'border-blue-400 bg-blue-50';
    return conn.correct ? 'border-green-500 bg-green-50' : 'border-red-400 bg-red-50';
}


// ─── Matching Pairs (memory) ─────────────────────────────────────────────────

const cards         = ref([]);
const flipped       = ref(new Set());
const matched       = ref(new Set());
const attempts      = ref(0);
const mpStep        = ref('playing'); // 'playing' | 'result'
let   lockBoard     = false;

function initMatchingPairs() {
    const pairs = props.match.pairs;
    const deck = [];
    pairs.forEach(p => {
        deck.push({ id: `a-${p.id}`, pairId: p.id, text: p.item_a, image: p.item_a_image });
        deck.push({ id: `b-${p.id}`, pairId: p.id, text: p.item_b, image: p.item_b_image });
    });
    cards.value   = shuffle(deck);
    flipped.value = new Set();
    matched.value = new Set();
    attempts.value = 0;
    mpStep.value   = 'playing';
    lockBoard      = false;
}

initMatchingPairs();

const currentFlipped = computed(() => [...flipped.value].filter(id => !matched.value.has(id)));

function flipCard(cardId) {
    if (lockBoard) return;
    if (matched.value.has(cardId)) return;
    if (flipped.value.has(cardId)) return;
    if (currentFlipped.value.length >= 2) return;

    flipped.value = new Set([...flipped.value, cardId]);

    if (currentFlipped.value.length === 2) {
        attempts.value++;
        lockBoard = true;

        const [id1, id2] = currentFlipped.value;
        const c1 = cards.value.find(c => c.id === id1);
        const c2 = cards.value.find(c => c.id === id2);

        if (c1.pairId === c2.pairId && c1.id !== c2.id) {
            // Match!
            setTimeout(() => {
                matched.value = new Set([...matched.value, id1, id2]);
                lockBoard = false;
                if (matched.value.size === cards.value.length) {
                    mpStep.value = 'result';
                }
            }, 600);
        } else {
            setTimeout(() => {
                const next = new Set(flipped.value);
                next.delete(id1);
                next.delete(id2);
                flipped.value = next;
                lockBoard = false;
            }, 1000);
        }
    }
}

function isFlipped(cardId) {
    return flipped.value.has(cardId) || matched.value.has(cardId);
}

function isMatched(cardId) {
    return matched.value.has(cardId);
}

function cardClass(cardId) {
    if (isMatched(cardId)) return 'border-green-400 bg-green-50';
    if (isFlipped(cardId)) return 'border-blue-400 bg-blue-50';
    return 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50 cursor-pointer';
}

const mpRating = computed(() => {
    const n = props.match.pairs.length;
    if (attempts.value <= n) return { text: 'Отлично! 🏆', color: 'text-green-600' };
    if (attempts.value <= n * 1.5) return { text: 'Хороший результат! 👍', color: 'text-blue-600' };
    if (attempts.value <= n * 2.5) return { text: 'Неплохо 💪', color: 'text-yellow-600' };
    return { text: 'Попробуйте ещё раз 🔄', color: 'text-red-500' };
});

// ─── True or False ────────────────────────────────────────────────────────────
// Показываем пару (A + B). Половина пар правильная, половина — перемешана.
// Пользователь отвечает: верная ли это пара?

const tfCards     = ref([]); // { itemA, itemB, isCorrect }
const tfCurrent   = ref(0);
const tfConfirmed = ref(false);
const tfAnswers   = ref([]);  // { userSaidTrue, isCorrect }
const tfStep      = ref('playing');

function initTrueOrFalse() {
    const pairs = props.match.pairs;
    const shuffled = shuffle(pairs);
    const cards = shuffled.map((pair, i) => {
        // Every other card gets a wrong item_b (from the next pair)
        const useCorrect = i % 2 === 0;
        const wrongIndex = (i + 1) % shuffled.length;
        return {
            itemA:      pair.item_a,
            itemAImage: pair.item_a_image,
            itemB:      useCorrect ? pair.item_b      : shuffled[wrongIndex].item_b,
            itemBImage: useCorrect ? pair.item_b_image : shuffled[wrongIndex].item_b_image,
            isCorrect:  useCorrect,
            correctB:   pair.item_b,
            correctBImage: pair.item_b_image,
        };
    });
    tfCards.value     = shuffle(cards);
    tfCurrent.value   = 0;
    tfConfirmed.value = false;
    tfAnswers.value   = [];
    tfStep.value      = 'playing';
}

initTrueOrFalse();

const tfCard    = computed(() => tfCards.value[tfCurrent.value]);
const tfTotal   = computed(() => tfCards.value.length);
const tfScore   = computed(() => tfAnswers.value.filter(a => a.correct).length);
const tfProgress = computed(() => Math.round((tfCurrent.value / tfTotal.value) * 100));

function tfAnswer(userSaidTrue) {
    if (tfConfirmed.value) return;
    tfConfirmed.value = true;
    const correct = userSaidTrue === tfCard.value.isCorrect;
    tfAnswers.value.push({ userSaidTrue, correct });

    if (tfCurrent.value === tfTotal.value - 1) {
        setTimeout(() => { tfStep.value = 'result'; }, 1000);
    }
}

function tfNext() {
    if (tfCurrent.value < tfTotal.value - 1) {
        tfCurrent.value++;
        tfConfirmed.value = false;
    } else {
        tfStep.value = 'result';
    }
}

const tfRating = computed(() => {
    const pct = tfScore.value / tfTotal.value;
    if (pct === 1)   return { text: 'Всё верно! 🏆', color: 'text-green-600' };
    if (pct >= 0.7)  return { text: 'Хороший результат! 👍', color: 'text-blue-600' };
    if (pct >= 0.4)  return { text: 'Неплохо, но есть куда расти 💪', color: 'text-yellow-600' };
    return { text: 'Попробуйте ещё раз 🔄', color: 'text-red-500' };
});
</script>

<template>
    <AppLayout>
        <Head :title="match.title" />

        <div>

            <!-- Admin draft warning -->
            <div
                v-if="!match.is_published && page.props.auth?.user?.is_admin"
                class="mb-4 flex items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800"
            >
                <i class="pi pi-exclamation-triangle mt-0.5 shrink-0 text-yellow-500" />
                <span>Матч не опубликован. <Link :href="route('admin.match.edit', match.id)" class="font-semibold underline hover:text-yellow-900">Опубликовать</Link>.</span>
            </div>

            <!-- Header -->
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('match.index')" class="text-gray-400 hover:text-gray-600">
                    <span class="text-xl">←</span>
                </Link>
                <div class="flex-1">
                    <h1 class="mb-1 text-xl font-bold text-gray-800">{{ match.title }}</h1>
                    <select
                        :value="mechanic"
                        @change="changeMechanic($event.target.value)"
                        class="rounded-lg border border-gray-200 bg-white pl-3 pr-8 py-1.5 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        <option v-for="opt in mechanicOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- ═══════════════════ MATCH UP ═══════════════════ -->
            <template v-if="mechanic === 'match_up'">

                <template v-if="!muChecked || muScore < match.pairs.length">
                    <p class="mb-4 text-sm text-gray-500">
                        Нажмите элемент слева, затем соответствующий элемент справа.
                        Нажмите на уже соединённый элемент, чтобы разъединить.
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Left column -->
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div
                                v-for="item in leftItems"
                                :key="item.pairId"
                                class="relative aspect-square rounded-2xl border-2 transition-all duration-150 select-none overflow-hidden"
                                :class="leftItemClass(item.pairId)"
                                @click="selectLeft(item.pairId)"
                            >
                                <img v-if="item.image" :src="imgSrc(item.image)" class="absolute inset-0 h-full w-full object-cover" />
                                <div
                                    class="absolute inset-x-0 bottom-0 flex items-end justify-center px-2 pb-1.5 pt-4"
                                    :class="item.image ? 'bg-gradient-to-t from-black/60 to-transparent' : 'inset-0 items-center justify-center'"
                                >
                                    <span
                                        class="text-center text-xl font-semibold leading-tight"
                                        :class="item.image ? 'text-white' : 'text-gray-800'"
                                    >{{ item.text }}</span>
                                </div>
                                <span v-if="muChecked && connectedLeft(item.pairId)" class="absolute right-1.5 top-1.5 text-sm drop-shadow">
                                    {{ connectedLeft(item.pairId).correct ? '✅' : '❌' }}
                                </span>
                            </div>
                        </div>

                        <!-- Right column -->
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div
                                v-for="item in rightItems"
                                :key="item.pairId"
                                class="relative aspect-square rounded-2xl border-2 transition-all duration-150 select-none overflow-hidden"
                                :class="rightItemClass(item.pairId)"
                                @click="selectRight(item.pairId)"
                            >
                                <img v-if="item.image" :src="imgSrc(item.image)" class="absolute inset-0 h-full w-full object-cover" />
                                <div
                                    class="absolute inset-x-0 bottom-0 flex items-end justify-center px-2 pb-1.5 pt-4"
                                    :class="item.image ? 'bg-gradient-to-t from-black/60 to-transparent' : 'inset-0 items-center justify-center'"
                                >
                                    <span
                                        class="text-center text-xl font-semibold leading-tight"
                                        :class="item.image ? 'text-white' : 'text-gray-800'"
                                    >{{ item.text }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hint: selected left -->
                    <p v-if="selectedLeft !== null" class="mt-3 text-center text-xs text-blue-500">
                        Теперь выберите соответствие справа
                    </p>

                    <div class="mt-6">
                        <button
                            @click="checkMatchUp"
                            :disabled="!muAllConnected"
                            class="w-full rounded-2xl bg-blue-500 py-4 text-base font-semibold text-white shadow transition hover:bg-blue-600 disabled:opacity-40"
                        >
                            Проверить
                        </button>
                    </div>
                </template>

                <!-- Result -->
                <template v-else>
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm">
                            <span class="text-4xl">{{ muScore === match.pairs.length ? '🏆' : '📚' }}</span>
                        </div>
                        <h2 class="mb-1 text-2xl font-bold text-gray-800">Результат</h2>
                        <p class="mb-2 text-5xl font-bold text-blue-500">
                            {{ muScore }}<span class="text-2xl text-gray-400">/{{ match.pairs.length }}</span>
                        </p>
                        <p class="mb-8 text-base font-medium text-green-600">
                            {{ muScore === match.pairs.length ? 'Все пары верные! 🎉' : 'Есть ошибки. Попробуйте ещё раз!' }}
                        </p>
                        <div class="flex w-full flex-col gap-3">
                            <button
                                @click="initMatchUp"
                                class="w-full rounded-2xl bg-blue-500 py-4 text-base font-semibold text-white shadow transition hover:bg-blue-600"
                            >
                                Попробовать ещё раз
                            </button>
                            <Link
                                :href="route('match.index')"
                                class="w-full rounded-2xl border-2 border-gray-200 py-4 text-center text-base font-semibold text-gray-700 transition hover:border-gray-300"
                            >
                                К списку упражнений
                            </Link>
                        </div>
                    </div>
                </template>
            </template>

            <!-- ═══════════════════ MATCHING PAIRS ═══════════════════ -->
            <template v-else-if="mechanic === 'matching_pairs'">

                <template v-if="mpStep === 'playing'">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm text-gray-500">Найдите все пары</p>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600">
                            Попыток: {{ attempts }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                        <div
                            v-for="card in cards"
                            :key="card.id"
                            class="relative aspect-square rounded-2xl border-2 transition-all duration-200 select-none overflow-hidden"
                            :class="cardClass(card.id)"
                            @click="flipCard(card.id)"
                        >
                            <!-- Back (hidden) -->
                            <div
                                v-if="!isFlipped(card.id)"
                                class="absolute inset-0 flex items-center justify-center bg-gray-100 rounded-2xl"
                            >
                                <i class="pi pi-question text-2xl text-gray-300" />
                            </div>

                            <!-- Front (shown) -->
                            <div v-else class="absolute inset-0">
                                <!-- Image fills the card -->
                                <img
                                    v-if="card.image"
                                    :src="imgSrc(card.image)"
                                    class="h-full w-full object-cover"
                                />
                                <!-- Text overlay (with gradient if image present) -->
                                <div
                                    v-if="card.text"
                                    class="absolute inset-x-0 bottom-0 px-1 pb-1 pt-4 flex items-end justify-center"
                                    :class="card.image ? 'bg-gradient-to-t from-black/60 to-transparent' : 'inset-0 items-center justify-center'"
                                >
                                    <span
                                        class="text-center text-xs font-semibold leading-tight line-clamp-3"
                                        :class="card.image ? 'text-white' : 'text-gray-800'"
                                    >
                                        {{ card.text }}
                                    </span>
                                </div>
                                <i v-if="isMatched(card.id)" class="pi pi-check-circle absolute right-1.5 top-1.5 text-green-500 drop-shadow" />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Result -->
                <template v-else>
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm">
                            <span class="text-4xl">{{ attempts <= match.pairs.length ? '🏆' : '🎯' }}</span>
                        </div>
                        <h2 class="mb-1 text-2xl font-bold text-gray-800">Все пары найдены!</h2>
                        <p class="mb-1 text-5xl font-bold text-blue-500">{{ attempts }}</p>
                        <p class="mb-2 text-sm text-gray-400">попыток</p>
                        <p class="mb-8 text-base font-medium" :class="mpRating.color">{{ mpRating.text }}</p>

                        <div class="flex w-full flex-col gap-3">
                            <button
                                @click="initMatchingPairs"
                                class="w-full rounded-2xl bg-blue-500 py-4 text-base font-semibold text-white shadow transition hover:bg-blue-600"
                            >
                                Сыграть ещё раз
                            </button>
                            <Link
                                :href="route('match.index')"
                                class="w-full rounded-2xl border-2 border-gray-200 py-4 text-center text-base font-semibold text-gray-700 transition hover:border-gray-300"
                            >
                                К списку упражнений
                            </Link>
                        </div>
                    </div>
                </template>
            </template>

            <!-- ═══════════════════ TRUE OR FALSE ═══════════════════ -->
            <template v-else-if="mechanic === 'true_or_false'">

                <template v-if="tfStep === 'playing'">
                    <!-- Progress -->
                    <div class="mb-6">
                        <div class="mb-1 flex justify-between text-xs text-gray-400">
                            <span>{{ tfCurrent + 1 }} / {{ tfTotal }}</span>
                            <span>{{ tfProgress }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-blue-500 transition-all duration-300" :style="{ width: tfProgress + '%' }" />
                        </div>
                    </div>

                    <!-- Pair cards -->
                    <div class="mb-6 grid grid-cols-2 gap-3">
                        <!-- Item A -->
                        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                            <div v-if="tfCard.itemAImage" class="aspect-square">
                                <img :src="imgSrc(tfCard.itemAImage)" class="h-full w-full object-cover" />
                            </div>
                            <div class="p-4 text-center text-base font-semibold text-gray-800">
                                {{ tfCard.itemA }}
                            </div>
                        </div>
                        <!-- Item B -->
                        <div
                            class="overflow-hidden rounded-2xl shadow-sm transition"
                            :class="!tfConfirmed ? 'bg-white' : tfAnswers.at(-1).correct === tfCard.isCorrect && tfCard.isCorrect ? 'bg-green-50 ring-2 ring-green-400' : !tfCard.isCorrect ? 'bg-red-50 ring-2 ring-red-400' : 'bg-white'"
                        >
                            <div v-if="tfCard.itemBImage" class="aspect-square">
                                <img :src="imgSrc(tfCard.itemBImage)" class="h-full w-full object-cover" />
                            </div>
                            <div class="p-4 text-center text-base font-semibold text-gray-800">
                                {{ tfCard.itemB }}
                            </div>
                        </div>
                    </div>

                    <!-- Feedback -->
                    <div v-if="tfConfirmed" class="mb-4 rounded-xl px-4 py-3 text-center text-sm font-medium"
                        :class="tfAnswers.at(-1).correct ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                        <template v-if="tfAnswers.at(-1).correct">Правильно! ✓</template>
                        <template v-else>
                            Неверно. {{ tfCard.isCorrect ? 'Эта пара верная ✅' : `Правильная пара: ${tfCard.itemA} — ${tfCard.correctB}` }}
                        </template>
                    </div>

                    <!-- Buttons -->
                    <div v-if="!tfConfirmed" class="grid grid-cols-2 gap-3">
                        <button @click="tfAnswer(true)" class="rounded-2xl border-2 border-green-400 bg-green-50 py-5 text-lg font-bold text-green-700 transition hover:bg-green-100">
                            ✅ True
                        </button>
                        <button @click="tfAnswer(false)" class="rounded-2xl border-2 border-red-400 bg-red-50 py-5 text-lg font-bold text-red-700 transition hover:bg-red-100">
                            ❌ False
                        </button>
                    </div>
                    <button v-else @click="tfNext" class="w-full rounded-2xl bg-gray-800 py-4 text-base font-semibold text-white shadow transition hover:bg-gray-900">
                        {{ tfCurrent < tfTotal - 1 ? 'Следующее →' : 'Посмотреть результат' }}
                    </button>
                </template>

                <!-- Result -->
                <template v-else>
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm">
                            <span class="text-4xl">{{ tfScore === tfTotal ? '🏆' : tfScore >= tfTotal * 0.7 ? '🎯' : '📚' }}</span>
                        </div>
                        <h2 class="mb-1 text-2xl font-bold text-gray-800">Результат</h2>
                        <p class="mb-2 text-5xl font-bold text-blue-500">{{ tfScore }}<span class="text-2xl text-gray-400">/{{ tfTotal }}</span></p>
                        <p class="mb-8 text-base font-medium" :class="tfRating.color">{{ tfRating.text }}</p>

                        <!-- Breakdown -->
                        <div class="mb-8 w-full rounded-3xl bg-white p-5 text-left shadow-sm">
                            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Разбор</p>
                            <div v-for="(card, i) in tfCards" :key="i" class="flex items-start gap-3 border-b border-gray-100 py-3 last:border-0">
                                <span class="mt-0.5 shrink-0 text-lg">{{ tfAnswers[i]?.correct ? '✅' : '❌' }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-700">{{ card.itemA }} — {{ card.itemB }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        {{ card.isCorrect ? 'Верная пара' : `Правильно: ${card.itemA} — ${card.correctB}` }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex w-full flex-col gap-3">
                            <button @click="initTrueOrFalse" class="w-full rounded-2xl bg-blue-500 py-4 text-base font-semibold text-white shadow transition hover:bg-blue-600">
                                Пройти ещё раз
                            </button>
                            <Link :href="route('match.index')" class="w-full rounded-2xl border-2 border-gray-200 py-4 text-center text-base font-semibold text-gray-700 transition hover:border-gray-300">
                                К списку упражнений
                            </Link>
                        </div>
                    </div>
                </template>
            </template>

        </div>
    </AppLayout>
</template>

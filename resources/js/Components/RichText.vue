<script setup>
import { computed } from 'vue'
import { parseRichText } from '@/lib/richText'

const props = defineProps({ text: { type: String, default: '' } })

const blocks = computed(() => parseRichText(props.text))
</script>

<template>
    <div class="space-y-3">
        <template v-for="(block, i) in blocks" :key="i">
            <p v-if="block.type === 'p'">
                <template v-for="(part, j) in block.parts" :key="j"><strong v-if="part.bold" class="font-semibold text-ink">{{ part.text }}</strong><template v-else>{{ part.text }}</template></template>
            </p>
            <ul v-else class="list-disc space-y-1.5 pl-5">
                <li v-for="(item, j) in block.items" :key="j" class="pl-1">
                    <template v-for="(part, k) in item" :key="k"><strong v-if="part.bold" class="font-semibold text-ink">{{ part.text }}</strong><template v-else>{{ part.text }}</template></template>
                </li>
            </ul>
        </template>
    </div>
</template>

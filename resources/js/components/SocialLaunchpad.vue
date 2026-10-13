<script setup lang="ts">
import {
    AlertCircle,
    Check,
    Copy,
    ExternalLink,
    Image as ImageIcon,
    Mail,
    RotateCcw,
    Sparkles,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import type { Event, SocialDrafts } from '@/types/event';

const props = defineProps<{
    event: Event;
    drafts: SocialDrafts;
    bannerUrl?: string | null;
}>();

type PlatformKey = 'linkedin' | 'twitter' | 'email';

const activeTab = ref<PlatformKey>('linkedin');
const copiedPlatform = ref<string | null>(null);
const copyTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

// Editable text drafts
const editedTexts = ref<Record<PlatformKey, string>>({
    linkedin: '',
    twitter: '',
    email: '',
});

// Editable subject line for email
const editedSubject = ref('');

// Sync prop changes into local editable state
watch(
    () => props.drafts,
    (newDrafts) => {
        if (newDrafts) {
            editedTexts.value = {
                linkedin: newDrafts.linkedin?.text ?? '',
                twitter: newDrafts.twitter?.text ?? '',
                email: newDrafts.email?.text ?? '',
            };
            editedSubject.value = newDrafts.email?.subject ?? '';
        }
    },
    { immediate: true, deep: true },
);

const currentDraft = computed(() => {
    return props.drafts?.[activeTab.value];
});

const currentText = computed({
    get: () => editedTexts.value[activeTab.value] ?? '',
    set: (val: string) => {
        editedTexts.value[activeTab.value] = val;
    },
});

const characterCount = computed(() => currentText.value.length);
const characterLimit = computed(() => currentDraft.value?.character_limit);

const isOverLimit = computed(() => {
    if (!characterLimit.value) return false;
    return characterCount.value > characterLimit.value;
});

const isDirty = computed(() => {
    const original = props.drafts?.[activeTab.value]?.text ?? '';
    const textChanged = currentText.value !== original;
    if (activeTab.value === 'email') {
        const originalSubject = props.drafts?.email?.subject ?? '';
        return textChanged || editedSubject.value !== originalSubject;
    }
    return textChanged;
});

function resetCurrentDraft(): void {
    const original = props.drafts?.[activeTab.value]?.text ?? '';
    editedTexts.value[activeTab.value] = original;
    if (activeTab.value === 'email') {
        editedSubject.value = props.drafts?.email?.subject ?? '';
    }
}

// Copy to clipboard with temporary visual feedback
async function copyToClipboard(): Promise<void> {
    const textToCopy = currentText.value;

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(textToCopy);
        } else {
            // Fallback for non-secure / older browser environments
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        copiedPlatform.value = activeTab.value;

        if (copyTimeout.value) {
            clearTimeout(copyTimeout.value);
        }

        copyTimeout.value = setTimeout(() => {
            copiedPlatform.value = null;
        }, 2500);
    } catch (e) {
        console.error('Failed to copy to clipboard', e);
    }
}

// External platform URLs
const externalShareUrl = computed(() => {
    if (activeTab.value === 'linkedin') {
        return 'https://www.linkedin.com/feed/';
    }

    if (activeTab.value === 'twitter') {
        const encodedText = encodeURIComponent(currentText.value);
        return `https://twitter.com/intent/tweet?text=${encodedText}`;
    }

    if (activeTab.value === 'email') {
        const subject = encodeURIComponent(editedSubject.value || currentDraft.value?.subject || '');
        const body = encodeURIComponent(currentText.value);
        return `mailto:?subject=${subject}&body=${body}`;
    }

    return null;
});

const isImageBanner = computed(() => {
    if (!props.bannerUrl) return false;
    const url = props.bannerUrl.toLowerCase();
    return (
        url.includes('.png') ||
        url.includes('.jpg') ||
        url.includes('.jpeg') ||
        url.includes('.webp') ||
        url.includes('.svg') ||
        url.includes('images.unsplash.com') ||
        url.includes('cloudinary.com')
    );
});

const bannerImageFailed = ref(false);

watch(
    () => props.bannerUrl,
    () => {
        bannerImageFailed.value = false;
    },
);
</script>

<template>
    <Card class="border-sidebar-border/70 dark:border-sidebar-border flex flex-col h-full">
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <div>
                        <CardTitle class="text-lg font-bold tracking-tight text-foreground">
                            Social Media Launchpad
                        </CardTitle>
                        <CardDescription class="text-xs text-muted-foreground">
                            Ready-to-publish marketing copy & banner asset hub
                        </CardDescription>
                    </div>
                </div>

                <Badge variant="outline" class="text-[11px] font-mono">
                    {{ event.type }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="space-y-4 flex-1 flex flex-col pt-0">
            <!-- 1. Banner Asset Hub Section -->
            <div class="rounded-xl border border-border/70 bg-muted/30 p-3.5 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                        <ImageIcon class="h-3.5 w-3.5 text-primary" />
                        Campaign Creative Asset
                    </span>

                    <a
                        v-if="bannerUrl"
                        :href="bannerUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                    >
                        <span>Open Asset</span>
                        <ExternalLink class="h-3 w-3" />
                    </a>
                </div>

                <!-- Banner Available -->
                <div v-if="bannerUrl" class="space-y-2">
                    <!-- Image preview if image URL -->
                    <div
                        v-if="isImageBanner && !bannerImageFailed"
                        class="relative overflow-hidden rounded-lg border border-border/60 bg-background max-h-40 group"
                    >
                        <img
                            :src="bannerUrl"
                            alt="Event Campaign Banner"
                            class="w-full h-36 object-cover transition-transform duration-300 group-hover:scale-105"
                            @error="bannerImageFailed = true"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2.5">
                            <span class="text-xs text-white font-medium">Click Open Asset to view full size</span>
                        </div>
                    </div>

                    <!-- Link Card fallback for Drive/Figma/Files -->
                    <div
                        v-else
                        class="flex items-center justify-between rounded-lg border border-border bg-background p-2.5 text-xs"
                    >
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <div class="h-7 w-7 rounded bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                                <ImageIcon class="h-3.5 w-3.5" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-medium text-foreground truncate">Approved Design Asset</div>
                                <div class="text-[11px] text-muted-foreground truncate">{{ bannerUrl }}</div>
                            </div>
                        </div>

                        <Button as-child size="sm" variant="secondary" class="h-7 px-2.5 text-xs shrink-0">
                            <a :href="bannerUrl" target="_blank" rel="noopener noreferrer">
                                Open Asset
                            </a>
                        </Button>
                    </div>
                </div>

                <!-- Banner Pending Pill -->
                <div
                    v-else
                    class="flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-300"
                >
                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                    <span>⏳ Banner design task is still pending</span>
                </div>
            </div>

            <!-- 2. Platform Navigation Tabs -->
            <div class="space-y-3 flex-1 flex flex-col">
                <div class="flex items-center justify-between border-b border-border pb-2">
                    <div class="flex items-center gap-1 p-0.5 rounded-lg bg-muted/60 text-xs">
                        <!-- LinkedIn Tab -->
                        <button
                            type="button"
                            @click="activeTab = 'linkedin'"
                            class="flex items-center gap-1.5 rounded-md px-3 py-1.5 font-medium transition-all"
                            :class="
                                activeTab === 'linkedin'
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <svg class="h-3.5 w-3.5 fill-current text-[#0A66C2]" viewBox="0 0 24 24">
                                <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 10.9v8.37H9.2V10.9H6.46M7.83 6.2a1.66 1.66 0 0 0-1.66 1.66c0 .92.74 1.66 1.66 1.66.92 0 1.66-.74 1.66-1.66 0-.92-.74-1.66-1.66-1.66Z" />
                            </svg>
                            <span>LinkedIn</span>
                        </button>

                        <!-- Twitter/X Tab -->
                        <button
                            type="button"
                            @click="activeTab = 'twitter'"
                            class="flex items-center gap-1.5 rounded-md px-3 py-1.5 font-medium transition-all"
                            :class="
                                activeTab === 'twitter'
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <svg class="h-3.5 w-3.5 fill-current text-foreground" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                            <span>X (Twitter)</span>
                        </button>

                        <!-- Newsletter / Email Tab -->
                        <button
                            type="button"
                            @click="activeTab = 'email'"
                            class="flex items-center gap-1.5 rounded-md px-3 py-1.5 font-medium transition-all"
                            :class="
                                activeTab === 'email'
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <Mail class="h-3.5 w-3.5 text-amber-500" />
                            <span>Newsletter</span>
                        </button>
                    </div>

                    <!-- Character Count / Limit Badge -->
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="isDirty"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="resetCurrentDraft"
                            class="h-6 px-1.5 text-[11px] text-muted-foreground hover:text-foreground"
                            title="Reset to default generated text"
                        >
                            <RotateCcw class="mr-1 h-3 w-3" />
                            Reset
                        </Button>

                        <span
                            class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-mono font-medium"
                            :class="
                                isOverLimit
                                    ? 'border-destructive/60 bg-destructive/10 text-destructive font-bold'
                                    : 'border-border bg-muted/40 text-muted-foreground'
                            "
                        >
                            <template v-if="characterLimit">
                                {{ characterCount }} / {{ characterLimit }}
                            </template>
                            <template v-else>
                                {{ characterCount }} chars
                            </template>
                        </span>
                    </div>
                </div>

                <!-- Email Subject Field (if on email tab) -->
                <div v-if="activeTab === 'email'" class="space-y-1.5">
                    <Label for="email_subject" class="text-xs font-semibold text-muted-foreground">
                        Subject Line
                    </Label>
                    <input
                        id="email_subject"
                        v-model="editedSubject"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                    />
                </div>

                <!-- Hashtags display if present -->
                <div
                    v-if="currentDraft?.hashtags && currentDraft.hashtags.length > 0"
                    class="flex flex-wrap items-center gap-1.5"
                >
                    <span class="text-[11px] font-medium text-muted-foreground">Tags:</span>
                    <span
                        v-for="tag in currentDraft.hashtags"
                        :key="tag"
                        class="rounded bg-primary/10 px-1.5 py-0.5 text-[11px] font-mono font-medium text-primary"
                    >
                        {{ tag }}
                    </span>
                </div>

                <!-- Draft Textarea Editor -->
                <div class="flex-1 flex flex-col space-y-1">
                    <Label :for="`draft_${activeTab}`" class="text-xs font-medium text-muted-foreground sr-only">
                        Draft copy
                    </Label>
                    <textarea
                        :id="`draft_${activeTab}`"
                        v-model="currentText"
                        rows="7"
                        class="w-full flex-1 min-h-[160px] rounded-lg border border-input bg-background p-3 text-xs leading-relaxed text-foreground shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring resize-y font-mono"
                        placeholder="Draft content..."
                    />
                </div>

                <!-- 3. Action Toolbar -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-border">
                    <!-- Primary Copy Button -->
                    <Button
                        type="button"
                        size="sm"
                        @click="copyToClipboard"
                        class="flex items-center gap-1.5 text-xs transition-colors"
                        :class="
                            copiedPlatform === activeTab
                                ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                : ''
                        "
                    >
                        <Check v-if="copiedPlatform === activeTab" class="h-3.5 w-3.5" />
                        <Copy v-else class="h-3.5 w-3.5" />
                        <span>{{ copiedPlatform === activeTab ? 'Copied! ✓' : 'Copy to Clipboard' }}</span>
                    </Button>

                    <!-- Secondary Platform Shortcut -->
                    <Button
                        v-if="externalShareUrl"
                        as-child
                        type="button"
                        variant="outline"
                        size="sm"
                        class="text-xs flex items-center gap-1.5"
                    >
                        <a :href="externalShareUrl" target="_blank" rel="noopener noreferrer">
                            <span>
                                <template v-if="activeTab === 'linkedin'">Open LinkedIn</template>
                                <template v-else-if="activeTab === 'twitter'">Post to X / Twitter</template>
                                <template v-else>Open Mail Client</template>
                            </span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

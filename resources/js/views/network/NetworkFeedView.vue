<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Network Feed</h1>

        <!-- Create Post -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <button
                v-if="!showCreateForm"
                @click="showCreateForm = true"
                class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
            >
                What's on your mind? Create a post...
            </button>

            <div v-else class="space-y-3">
                <textarea
                    v-model="postForm.body"
                    rows="4"
                    placeholder="Share an update, article, or opportunity..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 resize-none"
                />
                <div class="flex items-center gap-3">
                    <select
                        v-model="postForm.post_type"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                    >
                        <option value="update">Update</option>
                        <option value="article">Article</option>
                        <option value="opportunity">Opportunity</option>
                    </select>
                    <select
                        v-model="postForm.visibility"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                    >
                        <option value="public">Public</option>
                        <option value="connections">Connections only</option>
                        <option value="private">Private</option>
                    </select>
                    <div class="flex-1" />
                    <button @click="showCreateForm = false; postForm.body = ''" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button
                        @click="createPost"
                        :disabled="!postForm.body.trim() || posting"
                        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ posting ? 'Posting...' : 'Post' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <!-- Posts -->
        <div v-else class="space-y-4">
            <div
                v-for="post in posts"
                :key="post.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5"
            >
                <div class="flex items-start gap-3 mb-3">
                    <!-- Avatar -->
                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                        {{ initials(post.author_name) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ post.author_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ post.author_headline }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ timeAgo(post.created_at) }}</p>
                    </div>
                    <span :class="typeClass(post.post_type)" class="px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0">
                        {{ formatType(post.post_type) }}
                    </span>
                </div>

                <p class="text-gray-800 dark:text-gray-200 text-sm leading-relaxed whitespace-pre-line">{{ post.body }}</p>

                <div class="flex items-center gap-4 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ post.like_count ?? 0 }} likes</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ post.comment_count ?? 0 }} comments</span>
                </div>
            </div>

            <div v-if="posts.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                No posts yet. Be the first to share something!
            </div>
        </div>

        <!-- Load more -->
        <div v-if="hasMore && !loading" class="flex justify-center mt-6">
            <button
                @click="loadMore"
                :disabled="loadingMore"
                class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
            >
                {{ loadingMore ? 'Loading...' : 'Load more' }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface Post {
    id: number;
    author_name: string;
    author_headline?: string;
    body: string;
    post_type: string;
    visibility: string;
    like_count?: number;
    comment_count?: number;
    created_at: string;
}

const posts = ref<Post[]>([]);
const loading = ref(false);
const loadingMore = ref(false);
const posting = ref(false);
const showCreateForm = ref(false);
const hasMore = ref(false);
const page = ref(1);

const postForm = reactive({
    body: '',
    post_type: 'update',
    visibility: 'public',
});

function initials(name: string): string {
    return (name ?? '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
}

function typeClass(type: string): string {
    const classes: Record<string, string> = {
        update: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        article: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        opportunity: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
}

function formatType(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}

async function loadFeed(reset = false) {
    if (reset) {
        page.value = 1;
        loading.value = true;
    } else {
        loadingMore.value = true;
    }
    try {
        const res = await apiClient.get('/v1/network/feed', { params: { page: page.value } });
        const data = res.data?.data ?? [];
        if (reset) {
            posts.value = data;
        } else {
            posts.value.push(...data);
        }
        hasMore.value = !!res.data?.meta?.next_page_url || data.length >= 10;
    } catch {
        if (reset) posts.value = [];
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

async function loadMore() {
    page.value++;
    await loadFeed(false);
}

async function createPost() {
    if (!postForm.body.trim()) return;
    posting.value = true;
    try {
        await apiClient.post('/v1/network/posts', postForm);
        showCreateForm.value = false;
        postForm.body = '';
        postForm.post_type = 'update';
        postForm.visibility = 'public';
        notify.success('Post created');
        await loadFeed(true);
    } catch {
        notify.error('Failed to create post');
    } finally {
        posting.value = false;
    }
}

onMounted(() => loadFeed(true));
</script>

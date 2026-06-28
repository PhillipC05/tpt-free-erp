<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Recruitment</h1>

        <div v-if="dashboard" class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Open Positions</div>
                <div class="text-2xl font-bold text-blue-600">{{ dashboard.open_jobs }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Applications</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ dashboard.total_applications }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">New</div>
                <div class="text-2xl font-bold text-green-600">{{ dashboard.new_applications }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">In Interview</div>
                <div class="text-2xl font-bold text-yellow-600">{{ dashboard.in_interview }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Hired</div>
                <div class="text-2xl font-bold text-green-600">{{ dashboard.hired }}</div>
                <div class="text-xs text-gray-400">{{ dashboard.conversion_rate }}% conversion</div>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="tab = 'jobs'" :class="tabClass('jobs')">Jobs</button>
            <button @click="tab = 'pipeline'" :class="tabClass('pipeline')">Pipeline</button>
            <button @click="tab = 'interviews'" :class="tabClass('interviews')">Interviews</button>
        </div>

        <div v-if="tab === 'jobs'" class="space-y-4">
            <div class="flex justify-end">
                <button @click="showJobModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Post Job</button>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Code</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Title</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Dept</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Location</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Apps</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="job in jobs" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ job.job_code }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ job.title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ job.department?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ job.location ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">{{ job.applications_count ?? job.applications?.length ?? 0 }}</td>
                            <td class="px-4 py-3"><span :class="jobStatusClass(job.status)" class="text-xs px-2 py-1 rounded-full capitalize">{{ job.status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="job.status === 'draft'" @click="publishJob(job.id)" class="text-xs text-green-600 hover:text-green-800 mr-2">Publish</button>
                                <button v-if="job.status === 'open'" @click="closeJob(job.id)" class="text-xs text-red-600 hover:text-red-800">Close</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'pipeline'" class="space-y-4">
            <div class="grid grid-cols-6 gap-4">
                <div v-for="(stage, key) in pipeline" :key="key" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 capitalize">{{ key }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stage.count }}</div>
                    <div class="mt-3 space-y-1">
                        <div v-for="app in stage.applications.slice(0, 3)" :key="app.id" class="text-xs text-gray-600 dark:text-gray-400 truncate">
                            {{ app.candidate_name }}
                        </div>
                        <div v-if="stage.applications.length > 3" class="text-xs text-gray-400">+{{ stage.applications.length - 3 }} more</div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="tab === 'interviews'" class="space-y-4">
            <div v-if="!dashboard?.upcoming_interviews?.length" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center text-gray-500">No upcoming interviews</div>
            <div v-for="interview in dashboard?.upcoming_interviews ?? []" :key="interview.id" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center justify-between">
                <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ interview.application?.candidate_name }} — {{ interview.application?.job?.title }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ interview.interview_type }} &middot; {{ interview.duration_minutes }}min &middot; {{ interview.location ?? 'TBD' }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ new Date(interview.scheduled_at).toLocaleDateString() }}</div>
                    <div class="text-xs text-gray-500">{{ interview.interviewer?.first_name }} {{ interview.interviewer?.last_name }}</div>
                </div>
            </div>
        </div>

        <ModalDialog v-model="showJobModal" title="Post New Job">
            <form @submit.prevent="createJob" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Job Code</label>
                        <input v-model="jobForm.job_code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input v-model="jobForm.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="jobForm.description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                        <input v-model="jobForm.location" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Salary</label>
                        <input v-model.number="jobForm.salary_min" type="number" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Salary</label>
                        <input v-model.number="jobForm.salary_max" type="number" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showJobModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Create</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const tab = ref('jobs');
const jobs = ref<any[]>([]);
const dashboard = ref<any>(null);
const pipeline = ref<any>({});
const showJobModal = ref(false);
const jobForm = reactive({ job_code: '', title: '', description: '', location: '', salary_min: 0, salary_max: 0 });

function tabClass(t: string): string {
    return tab.value === t
        ? 'px-4 py-2 text-sm rounded-md bg-blue-600 text-white'
        : 'px-4 py-2 text-sm rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600';
}

function jobStatusClass(status: string): string {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-700', open: 'bg-green-100 text-green-700',
        on_hold: 'bg-yellow-100 text-yellow-700', closed: 'bg-red-100 text-red-700',
        filled: 'bg-blue-100 text-blue-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-500';
}

async function loadJobs() {
    try { const res = await apiClient.get('/recruitment/jobs'); jobs.value = res.data?.data ?? []; } catch { jobs.value = []; }
}

async function loadDashboard() {
    try { const res = await apiClient.get('/recruitment/dashboard'); dashboard.value = res.data?.data ?? null; } catch { /* ignore */ }
}

async function loadPipeline() {
    try { const res = await apiClient.get('/recruitment/pipeline'); pipeline.value = res.data?.data?.pipeline ?? {}; } catch { pipeline.value = {}; }
}

async function createJob() {
    try { await apiClient.post('/recruitment/jobs', jobForm); showJobModal.value = false; notify.success('Job created'); Object.assign(jobForm, { job_code: '', title: '', description: '', location: '', salary_min: 0, salary_max: 0 }); await loadJobs(); } catch { notify.error('Failed to create job'); }
}

async function publishJob(id: number) {
    try { await apiClient.post(`/recruitment/jobs/${id}/publish`); notify.success('Job published'); await loadJobs(); await loadDashboard(); } catch { notify.error('Failed to publish'); }
}

async function closeJob(id: number) {
    try { await apiClient.post(`/recruitment/jobs/${id}/close`); notify.success('Job closed'); await loadJobs(); await loadDashboard(); } catch { notify.error('Failed to close'); }
}

onMounted(() => { loadJobs(); loadDashboard(); loadPipeline(); });
</script>

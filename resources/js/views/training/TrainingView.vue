<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Training Management</h1>

        <div v-if="dashboard" class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Programs</div>
                <div class="text-2xl font-bold text-blue-600">{{ dashboard.total_programs }}</div>
                <div v-if="dashboard.mandatory_programs" class="text-xs text-red-600">{{ dashboard.mandatory_programs }} mandatory</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sessions</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ dashboard.total_sessions }}</div>
                <div class="text-xs text-green-600">{{ dashboard.upcoming_sessions }} upcoming</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Completion Rate</div>
                <div class="text-2xl font-bold text-green-600">{{ dashboard.completion_rate }}%</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Certifications</div>
                <div class="text-2xl font-bold text-purple-600">{{ dashboard.active_certifications }}</div>
                <div v-if="dashboard.expiring_certifications" class="text-xs text-orange-600">{{ dashboard.expiring_certifications }} expiring</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Enrollments</div>
                <div class="text-2xl font-bold text-blue-600">{{ dashboard.total_enrollments }}</div>
                <div class="text-xs text-green-600">{{ dashboard.completed_enrollments }} completed</div>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="tab = 'programs'" :class="tabClass('programs')">Programs</button>
            <button @click="tab = 'sessions'" :class="tabClass('sessions')">Sessions</button>
            <button @click="tab = 'certs'" :class="tabClass('certs')">Certifications</button>
        </div>

        <div v-if="tab === 'programs'" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Training Programs</span>
                <button @click="showProgramModal = true" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Add Program</button>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Code</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Duration</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Mandatory</th>
                        <th class="px-4 py-3 text-right text-xs text-gray-500">Sessions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="p in programs" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ p.code }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ p.name }}</td>
                        <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full capitalize" :class="typeClass(p.type)">{{ p.type }}</span></td>
                        <td class="px-4 py-3 text-gray-500">{{ p.duration_hours ? p.duration_hours + 'h' : '—' }}</td>
                        <td class="px-4 py-3"><span v-if="p.is_mandatory" class="text-xs text-red-600 font-medium">Required</span><span v-else class="text-xs text-gray-400">Optional</span></td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ p.sessions_count ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tab === 'sessions'" class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Training Sessions</span>
                    <button @click="showSessionModal = true" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Schedule Session</button>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Title</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Program</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Location</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Enrolled</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="s in sessions" :key="s.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ s.title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ s.program?.name }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-xs">{{ new Date(s.starts_at).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ s.location ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ s.enrollments?.length ?? 0 }}{{ s.max_participants ? '/' + s.max_participants : '' }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full capitalize" :class="sessionStatusClass(s.status)">{{ s.status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="s.status === 'scheduled'" @click="startSession(s.id)" class="text-xs text-green-600 hover:text-green-800 mr-2">Start</button>
                                <button v-if="s.status === 'in_progress'" @click="completeSession(s.id)" class="text-xs text-blue-600 hover:text-blue-800 mr-2">Complete</button>
                                <button @click="showEnrollModal(s)" class="text-xs text-purple-600 hover:text-purple-800">Enroll</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'certs'" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Certifications</span>
                <button @click="showCertModal = true" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Record Certification</button>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Employee</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Certification</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Issued</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Expiry</th>
                        <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="c in certifications" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ c.employee?.first_name }} {{ c.employee?.last_name }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ c.certification_name }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ c.issued_date }}</td>
                        <td class="px-4 py-3 text-xs" :class="isExpiringSoon(c.expiry_date) ? 'text-orange-600 font-medium' : 'text-gray-500'">{{ c.expiry_date ?? 'No expiry' }}</td>
                        <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full" :class="c.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">{{ c.status }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <button v-if="c.status === 'active'" @click="renewCert(c.id)" class="text-xs text-blue-600 hover:text-blue-800">Renew</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ModalDialog v-model="showProgramModal" title="Add Training Program">
            <form @submit.prevent="createProgram" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label><input v-model="programForm.code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label><input v-model="programForm.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" /></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label><select v-model="programForm.type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"><option value="onboarding">Onboarding</option><option value="compliance">Compliance</option><option value="skill">Skill</option><option value="safety">Safety</option><option value="leadership">Leadership</option><option value="other">Other</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration (hours)</label><input v-model.number="programForm.duration_hours" type="number" min="1" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" /></div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showProgramModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog v-model="showSessionModal" title="Schedule Session">
            <form @submit.prevent="createSession" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label><select v-model.number="sessionForm.program_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"><option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label><input v-model="sessionForm.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Starts At</label><input v-model="sessionForm.starts_at" type="datetime-local" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" /></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label><input v-model="sessionForm.location" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" /></div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showSessionModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog v-model="showEnrollModal" title="Enroll Employee">
            <form @submit.prevent="enrollEmployee" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Session</label>
                    <input type="text" :value="enrollingSession?.title" disabled class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employee ID</label>
                    <input v-model.number="enrollForm.employee_id" type="number" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showEnrollModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Enroll</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const tab = ref('programs');
const programs = ref<any[]>([]);
const sessions = ref<any[]>([]);
const certifications = ref<any[]>([]);
const dashboard = ref<any>(null);
const showProgramModal = ref(false);
const showSessionModal = ref(false);
const showEnrollModal = ref(false);
const enrollingSession = ref<any>(null);
const programForm = reactive({ code: '', name: '', type: 'skill', duration_hours: null as number | null });
const sessionForm = reactive({ program_id: 0, title: '', starts_at: '', location: '' });
const enrollForm = reactive({ employee_id: 0 });

function tabClass(t: string) { return tab.value === t ? 'px-4 py-2 text-sm rounded-md bg-blue-600 text-white' : 'px-4 py-2 text-sm rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'; }
function typeClass(type: string) { const m: Record<string, string> = { onboarding: 'bg-blue-100 text-blue-700', compliance: 'bg-red-100 text-red-700', skill: 'bg-green-100 text-green-700', safety: 'bg-yellow-100 text-yellow-700', leadership: 'bg-purple-100 text-purple-700', other: 'bg-gray-100 text-gray-700' }; return m[type] ?? 'bg-gray-100 text-gray-700'; }
function sessionStatusClass(s: string) { const m: Record<string, string> = { scheduled: 'bg-blue-100 text-blue-700', in_progress: 'bg-yellow-100 text-yellow-700', completed: 'bg-green-100 text-green-700', cancelled: 'bg-red-100 text-red-700' }; return m[s] ?? 'bg-gray-100 text-gray-500'; }
function isExpiringSoon(date: string | null): boolean { if (!date) return false; return (new Date(date).getTime() - Date.now()) / 86400000 <= 30; }

async function loadAll() {
    try { const [p, s, c, d] = await Promise.all([apiClient.get('/training/programs'), apiClient.get('/training/sessions'), apiClient.get('/training/certifications'), apiClient.get('/training/dashboard')]); programs.value = p.data?.data ?? []; sessions.value = s.data?.data ?? []; certifications.value = c.data?.data ?? []; dashboard.value = d.data?.data ?? null; } catch { /* ignore */ }
}

async function createProgram() { try { await apiClient.post('/training/programs', programForm); showProgramModal.value = false; notify.success('Program created'); Object.assign(programForm, { code: '', name: '', type: 'skill', duration_hours: null }); await loadAll(); } catch { notify.error('Failed'); } }
async function createSession() { try { await apiClient.post('/training/sessions', sessionForm); showSessionModal.value = false; notify.success('Session scheduled'); await loadAll(); } catch { notify.error('Failed'); } }
async function startSession(id: number) { try { await apiClient.post(`/training/sessions/${id}/start`); notify.success('Session started'); await loadAll(); } catch { notify.error('Failed'); } }
async function completeSession(id: number) { try { await apiClient.post(`/training/sessions/${id}/complete`); notify.success('Session completed'); await loadAll(); } catch { notify.error('Failed'); } }
function showEnrollModal(s: any) { enrollingSession.value = s; enrollForm.employee_id = 0; showEnrollModal.value = true; }
async function enrollEmployee() { try { await apiClient.post(`/training/sessions/${enrollingSession.value.id}/enroll`, { employee_id: enrollForm.employee_id }); showEnrollModal.value = false; notify.success('Employee enrolled'); await loadAll(); } catch { notify.error('Failed'); } }
async function renewCert(id: number) { try { await apiClient.post(`/training/certifications/${id}/renew`); notify.success('Certification renewed'); await loadAll(); } catch { notify.error('Failed'); } }

onMounted(loadAll);
</script>

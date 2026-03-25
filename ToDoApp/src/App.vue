<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue'

const search = ref('')
const showFilters = ref(false)

const createFilters = () => ({
  title: '',
  domain: '',
  date: '',
  priority: '',
  status: '',
})

const createTodoForm = () => ({
  title: '',
  description: '',
  endDate: '',
  priority: 'Mittel',
  status: 'Offen',
})

const draftFilters = ref(createFilters())
const activeFilters = ref(createFilters())
const showTodoModal = ref(false)
const showDomainModal = ref(false)
const activeDomainId = ref(null)
const activeTodoId = ref(null)
const filterPanel = reactive({
  top: 110,
  left: 0,
  width: 380,
  height: 590,
})
const todoForm = reactive(createTodoForm())
const domainForm = reactive({
  name: '',
  description: '',
})
const showDeleteDomainModal = ref(false)
const deletingDomainId = ref(null)
const todoTransferDecisions = ref([])

let dragState = null
let resizeState = null

const priorityOptions = ['Hoch', 'Mittel', 'Niedrig']
const statusOptions = ['Offen', 'In Arbeit', 'Erledigt']
const toneByPriority = {
  Hoch: 'is-red',
  Mittel: 'is-yellow',
  Niedrig: 'is-green',
}
const toneByStatus = {
  Offen: 'is-red',
  'In Arbeit': 'is-yellow',
  Erledigt: 'is-green',
}

const domains = ref([
  {
    id: 1,
    name: 'Beispiel',
    toDos: [
      {
        id: 1,
        title: 'Test123',
        description: 'Lorem Ipsum',
        endDate: '2026-03-21',
        priority: 'Hoch',
        status: 'In Arbeit',
      },
    ],
  },
])

const filteredDomains = computed(() => {
  const query = search.value.trim().toLowerCase()
  const filters = activeFilters.value
  const hasTodoFilters = [query, filters.title, filters.date, filters.priority, filters.status].some(Boolean)

  return domains.value
    .map((domain) => ({
      ...domain,
      toDos: domain.toDos.filter((todo) => matchesSearch(todo, query) && matchesFilters(todo, domain, filters)),
    }))
    .filter((domain) => {
      const domainMatches = !filters.domain || domain.name.toLowerCase().includes(filters.domain.toLowerCase())
      const searchMatches = !query || domain.name.toLowerCase().includes(query) || domain.toDos.length > 0

      if (!domainMatches || !searchMatches) {
        return false
      }

      return domain.toDos.length > 0 || !hasTodoFilters
    })
})

function matchesSearch(todo, query) {
  if (!query) {
    return true
  }

  return [todo.title, todo.description, todo.priority, todo.status, todo.endDate]
    .join(' ')
    .toLowerCase()
    .includes(query)
}

function matchesFilters(todo, domain, filters) {
  const titleMatch = !filters.title || todo.title.toLowerCase().includes(filters.title.toLowerCase())
  const domainMatch = !filters.domain || domain.name.toLowerCase().includes(filters.domain.toLowerCase())
  const dateMatch = !filters.date || todo.endDate === filters.date
  const priorityMatch = !filters.priority || todo.priority === filters.priority
  const statusMatch = !filters.status || todo.status === filters.status

  return titleMatch && domainMatch && dateMatch && priorityMatch && statusMatch
}

function applyFilters() {
  activeFilters.value = {
    ...draftFilters.value,
  }
}

function toggleFilters() {
  if (!showFilters.value) {
    filterPanel.top = 110
    filterPanel.left = Math.max(0, window.innerWidth - filterPanel.width - 32)
  }

  showFilters.value = !showFilters.value
}

function startFilterDrag(event) {
  if (event.target.closest('.filter-resize-hint')) {
    return
  }

  dragState = {
    startX: event.clientX,
    startY: event.clientY,
    startLeft: filterPanel.left,
    startTop: filterPanel.top,
  }

  window.addEventListener('mousemove', onFilterDrag)
  window.addEventListener('mouseup', stopFilterDrag)
}

function onFilterDrag(event) {
  if (!dragState) {
    return
  }

  filterPanel.left = Math.max(0, dragState.startLeft + event.clientX - dragState.startX)
  filterPanel.top = Math.max(0, dragState.startTop + event.clientY - dragState.startY)
}

function stopFilterDrag() {
  dragState = null
  window.removeEventListener('mousemove', onFilterDrag)
  window.removeEventListener('mouseup', stopFilterDrag)
}

function startFilterResize(event) {
  event.preventDefault()
  event.stopPropagation()

  resizeState = {
    direction: 'right',
    startX: event.clientX,
    startY: event.clientY,
    startWidth: filterPanel.width,
    startHeight: filterPanel.height,
  }

  window.addEventListener('mousemove', onFilterResize)
  window.addEventListener('mouseup', stopFilterResize)
}

function onFilterResize(event) {
  if (!resizeState) {
    return
  }

  if (resizeState.direction === 'left') {
    const nextWidth = Math.max(320, resizeState.startWidth - (event.clientX - resizeState.startX))
    const widthDelta = nextWidth - resizeState.startWidth
    filterPanel.width = nextWidth
    filterPanel.left = Math.max(0, resizeState.startLeft - widthDelta)
  } else {
    filterPanel.width = Math.max(320, resizeState.startWidth + event.clientX - resizeState.startX)
  }

  filterPanel.height = Math.max(360, resizeState.startHeight + event.clientY - resizeState.startY)
}

function stopFilterResize() {
  resizeState = null
  window.removeEventListener('mousemove', onFilterResize)
  window.removeEventListener('mouseup', stopFilterResize)
}

function startFilterResizeLeft(event) {
  event.preventDefault()
  event.stopPropagation()

  resizeState = {
    direction: 'left',
    startX: event.clientX,
    startY: event.clientY,
    startLeft: filterPanel.left,
    startWidth: filterPanel.width,
    startHeight: filterPanel.height,
  }

  window.addEventListener('mousemove', onFilterResize)
  window.addEventListener('mouseup', stopFilterResize)
}

function deleteDomain(domainId) {
  const domain = domains.value.find(d => d.id === domainId)
  if (!domain) return

  if (domain.toDos.length === 0 || domains.value.length === 1) {
    domains.value = domains.value.filter(d => d.id !== domainId)
    return
  }

  deletingDomainId.value = domainId
  todoTransferDecisions.value = domain.toDos.map(todo => ({
    todoId: todo.id,
    action: 'transfer',
    targetDomainId: domains.value.find(d => d.id !== domainId)?.id
  }))
  showDeleteDomainModal.value = true
}

function addDomain() {
  domainForm.name = ''
  domainForm.description = ''
  showDomainModal.value = true
}

function closeDomainModal() {
  showDomainModal.value = false
  domainForm.name = ''
  domainForm.description = ''
}

function saveDomain() {
  const trimmedName = domainForm.name.trim()

  if (!trimmedName) {
    return
  }

  domains.value.push({
    id: Date.now(),
    name: trimmedName,
    description: domainForm.description.trim(),
    toDos: [],
  })

  closeDomainModal()
}

function openTodoModal(domainId) {
  activeDomainId.value = domainId
  activeTodoId.value = null
  resetTodoForm()
  showTodoModal.value = true
}

function openEditTodoModal(domainId, todo) {
  activeDomainId.value = domainId
  activeTodoId.value = todo.id
  Object.assign(todoForm, todo)
  showTodoModal.value = true
}

function closeTodoModal() {
  showTodoModal.value = false
  activeDomainId.value = null
  activeTodoId.value = null
}

function resetTodoForm() {
  Object.assign(todoForm, createTodoForm())
}

function getActiveDomain() {
  return domains.value.find((entry) => entry.id === activeDomainId.value)
}

function getTodoPayload() {
  const title = todoForm.title.trim()
  const description = todoForm.description.trim()

  if (!title || !description || !todoForm.endDate) {
    return null
  }

  return {
    title,
    description,
    endDate: todoForm.endDate,
    priority: todoForm.priority,
    status: todoForm.status,
  }
}

function getPriorityTone(priority) {
  return toneByPriority[priority] ?? ''
}

function getStatusTone(status) {
  return toneByStatus[status] ?? ''
}

function getToneOptionStyle(tone) {
  if (tone === 'is-red') {
    return { backgroundColor: '#ff0000', color: '#ffffff' }
  }

  if (tone === 'is-yellow') {
    return { backgroundColor: '#ffe600', color: '#2d2d2d' }
  }

  if (tone === 'is-green') {
    return { backgroundColor: '#11ff09', color: '#143014' }
  }

  return {}
}

function saveTodo() {
  const domain = getActiveDomain()
  const payload = getTodoPayload()

  if (!domain || !payload) {
    return
  }

  if (activeTodoId.value) {
    const todo = domain.toDos.find((entry) => entry.id === activeTodoId.value)

    if (!todo) {
      return
    }

    Object.assign(todo, payload)
  } else {
    domain.toDos.push({
      id: Date.now(),
      ...payload,
    })
  }

  closeTodoModal()
}

function deleteTodo() {
  const domain = getActiveDomain()

  if (!domain || !activeTodoId.value) {
    return
  }

  domain.toDos = domain.toDos.filter((entry) => entry.id !== activeTodoId.value)
  closeTodoModal()
}

function getDeletingDomain() {
  return domains.value.find(d => d.id === deletingDomainId.value)
}

function getOtherDomains() {
  return domains.value.filter(d => d.id !== deletingDomainId.value)
}

function getTodoTitle(todoId) {
  const domain = getDeletingDomain()
  const todo = domain?.toDos.find(t => t.id === todoId)
  return todo?.title || ''
}

function setTransfer(decision, targetId) {
  decision.action = 'transfer'
  decision.targetDomainId = targetId
}

function setDelete(decision) {
  decision.action = 'delete'
  decision.targetDomainId = null
}

function cancelDeleteDomain() {
  showDeleteDomainModal.value = false
  deletingDomainId.value = null
  todoTransferDecisions.value = []
}

function confirmDeleteDomain() {
  const domain = getDeletingDomain()
  if (!domain) return

  for (const decision of todoTransferDecisions.value) {
    if (decision.action === 'transfer') {
      const targetDomain = domains.value.find(d => d.id === decision.targetDomainId)
      if (targetDomain) {
        const todo = domain.toDos.find(t => t.id === decision.todoId)
        if (todo) {
          targetDomain.toDos.push({ ...todo })
        }
      }
    }
  }

  domains.value = domains.value.filter(d => d.id !== deletingDomainId.value)
  showDeleteDomainModal.value = false
  deletingDomainId.value = null
  todoTransferDecisions.value = []
}

onBeforeUnmount(() => {
  stopFilterDrag()
  stopFilterResize()
})
</script>

<template>
  <div class="app-shell">
    <header class="top-nav">
      <div class="brand" aria-label="ToDo Startseite">
        <h1 class="brand-title">ToDo</h1>
        <img src="/img/logo.png" alt="ToDo Logo" class="brand-logo" />
      </div>

      <div class="nav-right">
        <form class="nav-search" @submit.prevent>
          <input v-model="search" type="search" placeholder="Suchen..." aria-label="Suche" />
          <button type="submit" aria-label="Suche starten">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="6.5" />
              <path d="M16 16L21 21" />
            </svg>
          </button>
        </form>

        <button type="button" class="nav-filter-toggle" @click="toggleFilters" aria-label="Filter">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6H20L14 13V19L10 21V13L4 6Z" />
          </svg>
        </button>
      </div>
    </header>

    <main class="main-content">
      <section class="toolbar-row">
        <button type="button" class="toolbar-link" @click="addDomain">
          <span>Bereich hinzufügen</span>
          <span class="toolbar-plus">+</span>
        </button>
      </section>

      <section v-if="showDomainModal" class="domain-modal-backdrop" @click.self="closeDomainModal">
        <div class="domain-modal">
          <div class="domain-modal-header">
            <h2>Bereich hinzufügen</h2>
            <button type="button" class="domain-modal-close" @click="closeDomainModal" aria-label="Popup schliessen">
              X
            </button>
          </div>

          <div class="domain-form">
            <label class="domain-field">
              <span>Name:</span>
              <input v-model="domainForm.name" type="text" placeholder="Name eingeben" />
            </label>

            <label class="domain-field">
              <span>Beschreibung:</span>
              <textarea v-model="domainForm.description" rows="4" placeholder="Beschreibung eingeben"></textarea>
            </label>
          </div>

          <div class="domain-modal-actions">
            <button type="button" class="domain-modal-cancel" @click="closeDomainModal">Abbrechen</button>
            <button type="button" class="domain-modal-save" @click="saveDomain">Speichern</button>
          </div>
        </div>
      </section>

      <section
        v-if="showFilters"
        class="filter-panel"
        :style="{
          top: `${filterPanel.top}px`,
          left: `${filterPanel.left}px`,
          width: `${filterPanel.width}px`,
          height: `${filterPanel.height}px`,
        }"
      >
        <div class="filter-panel-body">
          <div class="filter-header" @mousedown="startFilterDrag">
            <h2>Filter nach:</h2>
            <div class="filter-header-actions">
              <span class="filter-resize-hint">verschieben</span>
              <button type="button" class="filter-close" @click="showFilters = false" aria-label="Filter schliessen">
                X
              </button>
            </div>
          </div>

          <div class="filter-grid">
            <label class="filter-field">
              <span>Name:</span>
              <input v-model="draftFilters.title" type="text" placeholder="Titel filtern" />
            </label>

            <label class="filter-field">
              <span>Bereich:</span>
              <input v-model="draftFilters.domain" type="text" placeholder="Bereich filtern" />
            </label>

            <label class="filter-field">
              <span>Datum:</span>
              <input v-model="draftFilters.date" type="date" />
            </label>

            <label class="filter-field">
              <span>Priorität:</span>
              <select v-model="draftFilters.priority">
                <option value="">Alle</option>
                <option v-for="priority in priorityOptions" :key="priority" :value="priority">
                  {{ priority }}
                </option>
              </select>
            </label>

            <label class="filter-field">
              <span>Status:</span>
              <select v-model="draftFilters.status">
                <option value="">Alle</option>
                <option v-for="status in statusOptions" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
            </label>
          </div>

          <div class="filter-actions">
            <button type="button" class="apply-button" @click="applyFilters">Anwenden</button>
          </div>
        </div>

        <button
          type="button"
          class="filter-resize-handle filter-resize-handle-left"
          @mousedown="startFilterResizeLeft"
          aria-label="Filtergroesse links aendern"
        ></button>
        <button
          type="button"
          class="filter-resize-handle"
          @mousedown="startFilterResize"
          aria-label="Filtergroesse aendern"
        ></button>
      </section>

      <section v-for="domain in filteredDomains" :key="domain.id" class="domain-card">
        <div class="domain-header">

          <div class="domain-title">
            <h2>{{ domain.name }}</h2>
            <button type="button" class="domain-delete" @click="deleteDomain(domain.id)" aria-label="Bereich löschen">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 6h18v2H3V6zm2 2h14v14c0 1.1-.9 2-2 2H7c-1.1 0-2-.9-2-2V8zm3 2v10h2V10H8zm4 0v10h2V10h-2z"/>
              </svg>
            </button>
          </div>
          <button type="button" class="TaskADD" @click="openTodoModal(domain.id)">Todo Hinzufügen</button>
        </div>

        <table class="todo-table">
          <thead>
            <tr>
              <th>Titel</th>
              <th>Beschreibung</th>
              <th>Enddatum</th>
              <th>Priorität</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="todo in domain.toDos"
              :key="todo.id"
              class="todo-row"
              @click="openEditTodoModal(domain.id, todo)"
            >
              <td>{{ todo.title }}</td>
              <td>{{ todo.description }}</td>
              <td>{{ todo.endDate }}</td>
              <td>
                <span class="tone-pill" :class="getPriorityTone(todo.priority)">{{ todo.priority }}</span>
              </td>
              <td>
                <span class="tone-pill" :class="getStatusTone(todo.status)">{{ todo.status }}</span>
              </td>
            </tr>
            <tr v-if="domain.toDos.length === 0">
              <td colspan="5" class="empty-state">Keine Einträge für diese Suche.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section v-if="showTodoModal" class="todo-modal-backdrop" @click.self="closeTodoModal">
        <div class="todo-modal">
          <div class="todo-modal-header">
            <h2>{{ activeTodoId ? 'Todo bearbeiten' : 'Neues Todo' }}</h2>
            <button type="button" class="todo-modal-close" @click="closeTodoModal" aria-label="Popup schliessen">
              X
            </button>
          </div>

          <div class="todo-form">
            <label class="todo-field">
              <span>Titel:</span>
              <input v-model="todoForm.title" type="text" placeholder="Titel eingeben" />
            </label>

            <label class="todo-field">
              <span>Beschreibung:</span>
              <textarea v-model="todoForm.description" rows="4" placeholder="Beschreibung eingeben"></textarea>
            </label>

            <label class="todo-field">
              <span>Enddatum:</span>
              <input v-model="todoForm.endDate" type="date" />
            </label>

            <label class="todo-field">
              <span>Priorität:</span>
              <select v-model="todoForm.priority">
                <option
                  v-for="priority in priorityOptions"
                  :key="priority"
                  :value="priority"
                  :style="getToneOptionStyle(getPriorityTone(priority))"
                >
                  {{ priority }}
                </option>
              </select>
            </label>

            <label class="todo-field">
              <span>Status:</span>
              <select v-model="todoForm.status">
                <option
                  v-for="status in statusOptions"
                  :key="status"
                  :value="status"
                  :style="getToneOptionStyle(getStatusTone(status))"
                >
                  {{ status }}
                </option>
              </select>
            </label>
          </div>

          <div class="todo-modal-actions">
            <button v-if="activeTodoId" type="button" class="todo-modal-delete" @click="deleteTodo">Löschen</button>
            <button type="button" class="todo-modal-cancel" @click="closeTodoModal">Abbrechen</button>
            <button type="button" class="todo-modal-save" @click="saveTodo">Anwenden</button>
          </div>
        </div>
      </section>

      <section v-if="showDeleteDomainModal" class="delete-domain-modal-backdrop" @click.self="cancelDeleteDomain">
        <div class="delete-domain-modal">
          <div class="delete-domain-modal-header">
            <h2>Bereich "{{ getDeletingDomain()?.name }}" löschen</h2>
            <button type="button" class="delete-domain-modal-close" @click="cancelDeleteDomain" aria-label="Abbrechen">
              X
            </button>
          </div>
          <div class="delete-domain-modal-body">
            <p>Dieser Bereich enthält Todos. Wohin sollen sie verschoben werden?</p>
            <div v-for="decision in todoTransferDecisions" :key="decision.todoId" class="todo-transfer-item">
              <span class="todo-title">{{ getTodoTitle(decision.todoId) }}</span>
              <div class="transfer-options">
                <button
                  v-for="otherDomain in getOtherDomains()"
                  :key="otherDomain.id"
                  type="button"
                  class="transfer-button"
                  :class="{ active: decision.targetDomainId === otherDomain.id && decision.action === 'transfer' }"
                  @click="setTransfer(decision, otherDomain.id)"
                >
                  {{ otherDomain.name }}
                </button>
                <button
                  type="button"
                  class="delete-button"
                  :class="{ active: decision.action === 'delete' }"
                  @click="setDelete(decision)"
                >
                  Löschen
                </button>
              </div>
            </div>
          </div>
          <div class="delete-domain-modal-actions">
            <button type="button" class="cancel-button" @click="cancelDeleteDomain">Abbrechen</button>
            <button type="button" class="confirm-button" @click="confirmDeleteDomain">Bereich löschen</button>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
@font-face {
  font-family: 'Super Croissant';
  src: url('../Font/super_croissant/Super%20Croissant.ttf') format('truetype');
  font-display: swap;
}

.brand-title {
  margin: 0;
  margin-right: -24px;
  font-family: 'Super Croissant', cursive;
  font-size: clamp(4rem, 5.6vw, 5.8rem);
  line-height: 0.9;
  font-weight: 400;
  color: #1d4678;
  letter-spacing: 0.01em;
}
.app-shell {
  min-height: 100vh;
  background: linear-gradient(180deg, #f4f8fc 0%, #eef3f8 100%);
  color: #19324d;
}

.top-nav {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  min-height: 124px;
  padding: 8px 52px;
  background: #6fa1cf;
  border-bottom: 1px solid #7f7f7f;
  overflow: hidden;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0;
  min-height: 104px;
}

.brand-logo {
  width: min(210px, 22vw);
  max-height: 92px;
  margin-left: -22px;
  height: auto;
  display: block;
  object-fit: contain;
  mix-blend-mode: multiply;
}

.nav-search {
  display: flex;
  align-items: center;
  width: 255px;
  height: 58px;
  padding: 0 10px 0 18px;
  border: 4px solid #111111;
  border-radius: 999px;
  background: #ffffff;
  box-sizing: border-box;
}

.nav-search input {
  width: 0;
  flex: 1 1 auto;
  min-width: 0;
  border: 0;
  background: transparent;
  font-size: 1rem;
  color: #111111;
  outline: none;
}

.nav-search input::placeholder {
  color: #9a9a9a;
}

.nav-search button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 38px;
  width: 38px;
  height: 38px;
  border: 0;
  background: transparent;
  cursor: pointer;
  padding: 0;
}

.nav-search svg {
  width: 26px;
  height: 26px;
  fill: none;
  stroke: #111111;
  stroke-width: 2.2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.nav-filter-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border: 0;
  background: transparent;
  color: #19324d;
  cursor: pointer;
  padding: 0;
}

.nav-filter-toggle svg {
  width: 30px;
  height: 30px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linejoin: round;
}

.main-content {
  display: grid;
  gap: 20px;
  padding: 32px;
}

.toolbar-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 4px;
  color: #6d6d6d;
}

.toolbar-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 0;
  background: transparent;
  padding: 0;
  color: inherit;
  font-size: 1rem;
  cursor: pointer;
}

.toolbar-plus {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  border: 1px solid currentColor;
  border-radius: 999px;
  font-size: 0.82rem;
  line-height: 1;
}

.filter-panel {
  z-index: 20;
  position: fixed;
  padding: 0;
  border: 1px solid #aeb8c2;
  border-radius: 28px;
  background: #ffffff;
  box-shadow: 12px 12px 0 rgba(111, 161, 207, 0.18);
  overflow: hidden;
}

.filter-panel-body {
  height: 100%;
  padding: 12px 14px 36px;
  overflow: auto;
  box-sizing: border-box;
}

.filter-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
  cursor: move;
  user-select: none;
}

.filter-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.filter-panel h2 {
  font-size: 1.8rem;
  font-weight: 400;
  color: #6d6d6d;
}

.filter-resize-hint {
  font-size: 0.85rem;
  color: #8c98a3;
}

.filter-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid #aeb8c2;
  border-radius: 999px;
  background: #ffffff;
  color: #6d6d6d;
  font-size: 0.95rem;
  cursor: pointer;
}

.filter-resize-handle {
  position: absolute;
  bottom: 10px;
  width: 18px;
  height: 18px;
  border: 0;
  background:
    linear-gradient(135deg, transparent 0 45%, #8c98a3 45% 55%, transparent 55% 100%),
    linear-gradient(135deg, transparent 0 65%, #8c98a3 65% 75%, transparent 75% 100%);
  cursor: nwse-resize;
  padding: 0;
  z-index: 2;
}

.filter-resize-handle:not(.filter-resize-handle-left) {
  right: 10px;
}

.filter-resize-handle-left {
  left: 10px;
  background:
    linear-gradient(225deg, transparent 0 45%, #8c98a3 45% 55%, transparent 55% 100%),
    linear-gradient(225deg, transparent 0 65%, #8c98a3 65% 75%, transparent 75% 100%);
  cursor: nesw-resize;
}

.filter-grid {
  display: grid;
  gap: 14px;
}

.filter-field {
  display: grid;
  gap: 8px;
}

.filter-field span {
  font-size: 1rem;
  color: #6d6d6d;
}

.filter-field input,
.filter-field select {
  width: 100%;
  min-height: 42px;
  padding: 0 16px;
  border: 1px solid #aeb8c2;
  border-radius: 999px;
  background: #ffffff;
  color: #485869;
  font-size: 1rem;
  box-sizing: border-box;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 18px;
}

.apply-button {
  min-width: 160px;
  min-height: 42px;
  border: 1px solid #aeb8c2;
  border-radius: 999px;
  background: #f1f3f5;
  color: #6d6d6d;
  font-size: 1rem;
  cursor: pointer;
}

.domain-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 35;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(25, 50, 77, 0.22);
}

.domain-modal {
  width: min(420px, 100%);
  padding: 20px;
  border: 1px solid #c9d3dd;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(25, 50, 77, 0.18);
}

.domain-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.domain-modal-header h2 {
  margin: 0;
  font-size: 1.4rem;
  color: #4b647d;
}

.domain-modal-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 1px solid #aeb8c2;
  border-radius: 999px;
  background: #ffffff;
  color: #6d6d6d;
  cursor: pointer;
}

.domain-form {
  display: grid;
  gap: 14px;
}

.domain-field {
  display: grid;
  gap: 8px;
}

.domain-field span {
  color: #6d6d6d;
}

.domain-field input,
.domain-field textarea {
  width: 100%;
  min-height: 44px;
  padding: 12px 16px;
  border: 1px solid #aeb8c2;
  border-radius: 18px;
  background: #ffffff;
  color: #485869;
  font-size: 1rem;
  box-sizing: border-box;
}

.domain-field textarea {
  resize: vertical;
  min-height: 110px;
}

.domain-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.domain-modal-cancel,
.domain-modal-save {
  min-width: 120px;
  min-height: 42px;
  border-radius: 999px;
  font-size: 1rem;
  cursor: pointer;
}

.domain-modal-cancel {
  border: 1px solid #aeb8c2;
  background: #f1f3f5;
  color: #6d6d6d;
}

.domain-modal-save {
  border: 1px solid #aeb8c2;
  background: #f1f3f5;
  color: #6d6d6d;
}

.todo-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(25, 50, 77, 0.28);
}

.todo-modal {
  width: min(480px, 100%);
  max-height: min(85vh, 760px);
  padding: 20px;
  border: 1px solid #c9d3dd;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(25, 50, 77, 0.18);
  overflow: auto;
}

.todo-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.todo-modal-header h2 {
  font-size: 1.5rem;
  color: #4b647d;
}

.todo-modal-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 1px solid #aeb8c2;
  border-radius: 999px;
  background: #ffffff;
  color: #6d6d6d;
  cursor: pointer;
}

.todo-form {
  display: grid;
  gap: 14px;
}

.todo-field {
  display: grid;
  gap: 8px;
}

.todo-field span {
  color: #6d6d6d;
}

.todo-field input,
.todo-field textarea,
.todo-field select {
  width: 100%;
  min-height: 44px;
  padding: 12px 16px;
  border: 1px solid #aeb8c2;
  border-radius: 18px;
  background: #ffffff;
  color: #485869;
  font-size: 1rem;
  box-sizing: border-box;
}

.todo-field textarea {
  resize: vertical;
  min-height: 110px;
}

.todo-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.todo-modal-cancel,
.todo-modal-save,
.todo-modal-delete {
  min-width: 120px;
  min-height: 42px;
  border-radius: 999px;
  font-size: 1rem;
  cursor: pointer;
}

.todo-modal-cancel {
  border: 1px solid #aeb8c2;
  background: #f1f3f5;
  color: #6d6d6d;
}

.todo-modal-save {
  border: 1px solid #d3d8de;
  background: #f1f3f5;
  color: #6d6d6d;
}

.todo-modal-delete {
  border: 0;
  background: #ff0000;
  color: #ffffff;
}

.domain-card {
  padding: 20px;
  border: 1px solid #d6e1ec;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 18px 40px rgba(53, 89, 122, 0.08);
}

.domain-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.domain-header h2 {
  font-size: 1.35rem;
  font-weight: 700;
}

.TaskADD {
  padding: 10px 14px;
  border: 0;
  border-radius: 10px;
  background: #66a1cf;
  color: #ffffff;
  font-weight: 700;
  cursor: pointer;
}

.todo-table {
  width: 100%;
  border-collapse: collapse;
}

.todo-table th,
.todo-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #e4ebf3;
  text-align: left;
}

.tone-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 96px;
  padding: 7px 14px;
  border-radius: 999px;
  font-weight: 700;
  line-height: 1.1;
}

.is-red {
  background: #ff0000;
  color: #ffffff;
}

.is-yellow {
  background: #ffe600;
  color: #2d2d2d;
}

.is-green {
  background: #11ff09;
  color: #143014;
}

.todo-table th {
  font-weight: 700;
  color: #4b647d;
}

.todo-row {
  cursor: pointer;
}

.todo-row:hover td {
  background: rgba(111, 161, 207, 0.08);
}

.empty-state {
  text-align: center;
  color: #6f859c;
}

@media (max-width: 980px) {
  .top-nav {
    justify-content: center;
    min-height: auto;
    padding: 20px 24px;
  }

  .brand {
    position: static;
    transform: none;
  }

  .nav-search {
    width: min(100%, 320px);
    height: 54px;
  }

  .nav-filter-toggle {
    flex: 0 0 auto;
  }

  .brand-logo {
    width: min(235px, 58vw);
  }
}

@media (max-width: 720px) {
  .main-content {
    padding: 20px;
  }

  .filter-panel {
    max-width: none;
  }

  .toolbar-row {
    padding: 0;
  }

  .domain-header {
    flex-direction: column;
    align-items: stretch;
  }

  .todo-table {
    display: block;
    overflow-x: auto;
  }
}

.domain-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.domain-delete {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: 0;
  background: transparent;
  color: #d32f2f;
  cursor: pointer;
  padding: 0;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.domain-delete:hover {
  background: rgba(211, 47, 47, 0.1);
}

.domain-delete svg {
  width: 16px;
  height: 16px;
  fill: currentColor;
}

.delete-domain-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.delete-domain-modal {
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.delete-domain-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.delete-domain-modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #19324d;
}

.delete-domain-modal-close {
  background: transparent;
  border: 0;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
}

.delete-domain-modal-body {
  padding: 20px;
}

.delete-domain-modal-body p {
  margin: 0 0 20px 0;
  color: #19324d;
}

.todo-transfer-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
}

.todo-transfer-item:last-child {
  border-bottom: 0;
}

.todo-title {
  font-weight: 500;
  color: #19324d;
  flex: 1;
}

.transfer-options {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.transfer-button,
.delete-button {
  padding: 6px 12px;
  border: 1px solid #ccc;
  background: #f9f9f9;
  border-radius: 999px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.transfer-button:hover,
.delete-button:hover {
  background: #e0e0e0;
}

.transfer-button.active {
  background: #6fa1cf;
  color: white;
  border-color: #6fa1cf;
}

.delete-button.active {
  background: #d32f2f;
  color: white;
  border-color: #d32f2f;
}

.delete-domain-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #e0e0e0;
}

.cancel-button {
  padding: 10px 20px;
  border: 1px solid #ccc;
  background: #f9f9f9;
  border-radius: 999px;
  cursor: pointer;
  font-size: 1rem;
}

.cancel-button:hover {
  background: #e0e0e0;
}

.confirm-button {
  padding: 10px 20px;
  border: 0;
  background: #d32f2f;
  color: white;
  border-radius: 999px;
  cursor: pointer;
  font-size: 1rem;
}

.confirm-button:hover {
  background: #b71c1c;
}
</style>



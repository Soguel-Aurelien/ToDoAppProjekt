<script setup>
import { computed, ref } from 'vue'

const search = ref('')

const domains = ref([
  {
    id: 1,
    name: 'Schule',
    toDos: [
      {
        id: 1,
        title: 'Praesentation fertigstellen',
        description: 'Folien pruefen und Quellen ergaenzen',
        endDate: '2026-03-21',
        priority: 'Hoch',
        status: 'Offen',
      },
      {
        id: 2,
        title: 'Mathe ueben',
        description: 'Zwei Aufgabenblaetter rechnen',
        endDate: '2026-03-19',
        priority: 'Mittel',
        status: 'In Arbeit',
      },
    ],
  },
  {
    id: 2,
    name: 'Privat',
    toDos: [
      {
        id: 3,
        title: 'Einkaufen',
        description: 'Milch, Brot, Gemuese',
        endDate: '2026-03-18',
        priority: 'Niedrig',
        status: 'Offen',
      },
    ],
  },
])

const filteredDomains = computed(() => {
  const query = search.value.trim().toLowerCase()

  if (!query) {
    return domains.value
  }

  return domains.value
    .map((domain) => ({
      ...domain,
      toDos: domain.toDos.filter((todo) =>
        [todo.title, todo.description, todo.priority, todo.status]
          .join(' ')
          .toLowerCase()
          .includes(query),
      ),
    }))
    .filter((domain) => domain.name.toLowerCase().includes(query) || domain.toDos.length > 0)
})
</script>

<template>
  <div class="app-shell">
    <header class="top-nav">
      <div class="nav-spacer"></div>

      <div class="brand" aria-label="ToDo Startseite">
        <img src="/img/logo.png" alt="ToDo Logo" class="brand-logo" />
      </div>

      <form class="nav-search" @submit.prevent>
        <input v-model="search" type="search" placeholder="Suchen..." aria-label="Suche" />
        <button type="submit" aria-label="Suche starten">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6.5" />
            <path d="M16 16L21 21" />
          </svg>
        </button>
      </form>
    </header>

    <main class="main-content">
      <section v-for="domain in filteredDomains" :key="domain.id" class="domain-card">
        <div class="domain-header">
          <h2>{{ domain.name }}</h2>
<<<<<<< Updated upstream
          <button type="button" class="TaskADD">Todo Hinzufügen</button>
        </div>

        <table class="todo-table">
          <thead>
            <tr>
              <th>Titel</th>
              <th>Beschreibung</th>
              <th>Enddatum</th>
              <th>Prioritaet</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="todo in domain.toDos" :key="todo.id">
              <td>{{ todo.title }}</td>
              <td>{{ todo.description }}</td>
              <td>{{ todo.endDate }}</td>
              <td>{{ todo.priority }}</td>
              <td>{{ todo.status }}</td>
            </tr>
            <tr v-if="domain.toDos.length === 0">
              <td colspan="5" class="empty-state">Keine Eintraege fuer diese Suche.</td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: linear-gradient(180deg, #f4f8fc 0%, #eef3f8 100%);
  color: #19324d;
}

.top-nav {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 20px;
  min-height: 124px;
  padding: 8px 52px;
  background: #6fa1cf;
  border-bottom: 1px solid #7f7f7f;
  overflow: hidden;
}

.nav-spacer {
  min-height: 1px;
}

.brand {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 104px;
}

.brand-logo {
  width: min(360px, 40vw);
  max-height: 96px;
  height: auto;
  display: block;
  object-fit: contain;
  mix-blend-mode: multiply;
}

.nav-search {
  justify-self: end;
  display: flex;
  align-items: center;
  width: min(298px, 100%);
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

.main-content {
  display: grid;
  gap: 20px;
  padding: 32px;
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

.TaskADD  {
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

.todo-table th {
  font-weight: 700;
  color: #4b647d;
}

.empty-state {
  text-align: center;
  color: #6f859c;
}

@media (max-width: 980px) {
  .top-nav {
    grid-template-columns: 1fr;
    justify-items: center;
    min-height: auto;
    padding: 20px 24px;
  }

  .nav-spacer {
    display: none;
  }

  .nav-search {
    justify-self: center;
    width: min(100%, 320px);
    height: 54px;
  }

  .brand-logo {
    width: min(360px, 72vw);
  }
}

@media (max-width: 720px) {
  .main-content {
    padding: 20px;
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
</style>

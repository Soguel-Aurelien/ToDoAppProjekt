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
        title: 'Präsentation fertigstellen',
        description: 'Folien prüfen und Quellen ergänzen',
        endDate: '2026-03-21',
        priority: 'Hoch',
        status: 'Offen',
      },
      {
        id: 2,
        title: 'Mathe üben',
        description: 'Zwei Aufgabenblätter rechnen',
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
        description: 'Milch, Brot, Gemüse',
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
    <header class="header">
      <div>
        <p class="eyebrow">ToDo App</p>
        <h1>Meine Aufgaben</h1>
      </div>
      <input v-model="search" type="text" placeholder="Suchen..." class="search-bar" />
    </header>

    <main class="main-content">
      <section v-for="domain in filteredDomains" :key="domain.id" class="domain-card">
        <div class="domain-header">
          <h2>{{ domain.name }}</h2>
          <button type="button" class="action-button">Bereich hinzufügen</button>
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
            <tr v-for="todo in domain.toDos" :key="todo.id">
              <td>{{ todo.title }}</td>
              <td>{{ todo.description }}</td>
              <td>{{ todo.endDate }}</td>
              <td>{{ todo.priority }}</td>
              <td>{{ todo.status }}</td>
            </tr>
            <tr v-if="domain.toDos.length === 0">
              <td colspan="5" class="empty-state">Keine Einträge für diese Suche.</td>
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
  padding: 32px;
  background: linear-gradient(180deg, #f4f8fc 0%, #eef3f8 100%);
  color: #19324d;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 16px;
  margin-bottom: 24px;
}

.eyebrow {
  margin-bottom: 4px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #5c7a99;
}

h1 {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
}

.search-bar {
  width: min(320px, 100%);
  padding: 12px 14px;
  border: 1px solid #c4d2e0;
  border-radius: 12px;
  background: #fff;
}

.main-content {
  display: grid;
  gap: 20px;
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

.action-button {
  padding: 10px 14px;
  border: 0;
  border-radius: 10px;
  background: #66a1cf;
  color: #fff;
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

@media (max-width: 720px) {
  .app-shell {
    padding: 20px;
  }

  .header,
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

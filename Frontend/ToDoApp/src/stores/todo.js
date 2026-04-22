import { ref } from 'vue'
import { defineStore } from 'pinia'

const defaultDomains = () => [
  {
    id: 1,
    name: 'Beispiel',
    isProtected: false,
    password: '',
    isUnlocked: true,
    toDos: [
      {
        id: 1,
        title: 'Test123',
        description: 'Lorem Ipsum',
        contactEmail: 'contact@example.com',
        endDate: '2026-03-21',
        priority: 'Hoch',
        status: 'In Arbeit',
      },
    ],
  },
]

export const useTodoStore = defineStore(
  'todo',
  () => {
    const domains = ref(defaultDomains())

    return {
      domains,
    }
  },
  {
    persist: {
      key: 'todo-app-storage',
      storage: localStorage,
    },
  },
)

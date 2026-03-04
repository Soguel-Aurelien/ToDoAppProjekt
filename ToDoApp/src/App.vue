<script setup>
import { RouterLink, RouterView } from 'vue-router'
import HelloWorld from './components/HelloWorld.vue'
</script>

<template>
  <header class="header">

    <h1>ToDo</h1>
    <img src="" alt="">
    <input type="text" v-model="search" placeholder="Search..." class="searchBar">
  </header>

  <!--Muss noch die kategorien und unterkategorien erstellen-->
  <main class="mainContent">
    <div class="domains" v-for="domains in domain" :key="domain.id">
      <div class="domainName">
        <h2>{{ domain.name }}</h2>
        <img src="" alt="">
      </div>

      <div>

      </div>

      <div class="toDoHead">
        <tr>
          <th>Titel</th>
          <th>Beschreibung</th>
          <th>Enddatum</th>
          <th>Priorität</th>
          <th>Status</th>
        </tr>
        <tr class="toDo" v-for="toDo in domain.toDos" :key="toDo.id">
          <td>{{ toDo.titel }}</td>
          <td>{{ toDo.description }}</td>
          <td>{{ toDo.enddate }}</td>
          <td>{{ toDo.priority }}</td>
          <td>{{ toDo.status }}</td>

          <!--Papierkorb icone hinzufügen-->
          <td><img src="" alt=""></td>
        </tr>
      </div>
    </div>

  </main>
</template>

<style scoped>
header {
  line-height: 1.5;
  max-height: 100vh;
}

.logo {
  display: block;
  margin: 0 auto 2rem;
}

nav {
  width: 100%;
  font-size: 12px;
  text-align: center;
  margin-top: 2rem;
}

nav a.router-link-exact-active {
  color: var(--color-text);
}

nav a.router-link-exact-active:hover {
  background-color: transparent;
}

nav a {
  display: inline-block;
  padding: 0 1rem;
  border-left: 1px solid var(--color-border);
}

nav a:first-of-type {
  border: 0;
}

@media (min-width: 1024px) {
  header {
    display: flex;
    place-items: center;
    padding-right: calc(var(--section-gap) / 2);
  }

  .logo {
    margin: 0 2rem 0 0;
  }

  header .wrapper {
    display: flex;
    place-items: flex-start;
    flex-wrap: wrap;
  }

  nav {
    text-align: left;
    margin-left: -1rem;
    font-size: 1rem;

    padding: 1rem 0;
    margin-top: 1rem;
  }
}
</style>



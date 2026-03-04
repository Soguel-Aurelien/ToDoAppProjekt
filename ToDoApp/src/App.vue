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

<style></style>

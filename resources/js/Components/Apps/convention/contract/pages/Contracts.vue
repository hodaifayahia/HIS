<script setup>
import Contract_table from '../../company/tables/Contract_table.vue';
</script>

<template>
    <div class="content">
      <div class="title">
        <h1>Contracts</h1>
      </div>
      <Contract_table />
  </div>
</template>

<style scoped>
.container {
  display: flex;
  flex-direction: row;
}

.content {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding-top: 10px;
  padding-right: 20px;
  padding-bottom: 20px;
}

.title h1 {
  margin-top: 10px;
  margin-bottom: 20px;
  font-weight: bold;
  font-size: 2rem;
}
/* .card{
  margin: 1.5rem;
  display: flex;
  flex-direction: column;
}
.card h2{
  font-weight: bold;
  font-size: 1.4rem;
  line-height: 2.5rem;
  display: inline;
} */

</style>
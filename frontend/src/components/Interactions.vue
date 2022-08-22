<!-- This example requires Tailwind CSS v2.0+ -->
<template>
  <div class="sm:flex-auto mb-4">
    <h1 class="text-xl font-semibold text-gray-900">Interactions</h1>
  </div>
  <ul role="list" class="divide-y divide-gray-200">
    <li v-for="person in people" :key="person.count" class="py-4 flex first:pt-0 last:pb-0">
      <img class="h-10 w-10 rounded-full" :src="person.image" alt="" />
      <div class="ml-3">
        <p class="text-sm font-medium text-gray-900">{{ person.sender_name }}</p>
        <p class="text-sm text-gray-500">Sends <span class="font-extrabold">{{ person.count }}</span> to <span class="font-medium">{{ person.receiver_name }}</span></p>
      </div>
    </li>
  </ul>
</template>

<script setup lang="ts">
const people = [
  {
    sender_name: 'Calvin Hawkins',
    receiver_name: 'Kristen Ramos',
    count: '2',
    image:
      'https://images.unsplash.com/photo-1491528323818-fdd1faba62cc?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
  },
  {
    sender_name: 'Kristen Ramos',
    receiver_name: 'Kristen Ramos',
    count: '4',
    image:
      'https://images.unsplash.com/photo-1550525811-e5869dd03032?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
  },
  {
    sender_name: 'Ted Fox',
    receiver_name: 'Kristen Ramos',
    count: '2',
    image:
      'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
  },
]
</script>
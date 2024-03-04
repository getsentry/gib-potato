<template>
    <p class="text-sm font-normal py-2.5 text-gray-900 dark:text-white" v-html="formattedMessage">
        
  </p>
  </template>
  
  <script>
  export default {
    props: ['message'],
    computed: {
        formattedMessage() {
            return this.message
            .replace(/<(@[^>]+)>/g, (_match, p1) => `<span class="bg-blue-700 p-0.5 px-1 rounded-lg">${p1}</span>`)
            .replace(/ (\w+) <(http[^>]+)>/g, (_match, p1, p2) => ` <a class="underline" href="${p2}">${p1}</a> `)
            .replace(/:potato:/g, '🥔');
        },  
    },
  }
  </script>
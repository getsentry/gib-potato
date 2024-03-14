<template>
    <p class="text-sm pt-2.5" v-html="formattedMessage"></p>
</template>
  
<script>
    export default {
        name: 'FormattedMessage',
        props: ['message'],
        computed: {
            formattedMessage() {
                return this.message
                    .replace(/<(@[^>]+)>/g, (_match, p1) => `<span class="bg-blue-500 text-blue-500 bg-opacity-30 p-0.5 px-1 rounded-sm">${p1}</span>`)
                    .replace(/ (\w+) <(http[^>]+)>/g, (_match, p1, p2) => ` <a class="underline" href="${p2}">${p1}</a> `)
                    .replace(/:potato:/g, '🥔');
            },  
        },
    }
</script>

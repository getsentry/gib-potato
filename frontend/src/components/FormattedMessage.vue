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
                    .replace(/<(@[^>]+)>/g, (_match, p1) => `<span class="bg-blue-500/30 text-blue-500 p-0.5 px-1 rounded-xs">${p1}</span>`)
                    .replace(/ (\w+) <(http[^>]+)>/g, (_match, p1, p2) => ` <a class="underline" href="${p2}">${p1}</a> `)
                    .replace(/:potato:/g, '🥔');
            },  
        },
    }
</script>

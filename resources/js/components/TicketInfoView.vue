<template>
    <div>
        <span :style="{color :item.color ? item.color : null}"
            :class="item.variant ? 'btn btn-' + item.variant : ''"
        >{{ item.text }}</span>
    </div>
</template>

<script>
    export default {
        props: {
            data: {
                type: Object,
                default: () => {}
            },
            widget: {
                type: Object,
                default: () => {}
            },
            crudType: {
                type: String,
                default: 'index'
            }
        },
        computed: {
            item(newValue, oldValue) {
                return this.data[this.widget.name];
            }
        },
    }
</script>

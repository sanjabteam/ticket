var SanjabTicketPlugin = {};
SanjabTicketPlugin.install = function (Vue, options) {
    Vue.component('ticket-info-view', require('./components/TicketInfoView.vue').default);
    Vue.component('ticket-messages', require('./components/TicketMessages.vue').default);
}
export default SanjabTicketPlugin;

<template>
    <div>
        <b-card class="messages-container sanjab-customized-scrollbar">
            <b-row v-for="(message, index) in messages" :key="index">
                <b-col>
                    <b-card :title="message.user.name" class="balon-card" :bg-variant="ticket.user.id == message.user.id ? 'secondary' : 'success'" :class="{'balon-card-user' : ticket.user.id == message.user.id}">
                        <b-card-text>
                            {{ message.text }}
                        </b-card-text>
                        <template v-slot:footer>
                            <small class="text-light">
                                {{ message.created_at_diff }}
                                <span class="seen" :title="message.seen_by ? message.seen_by.name : ''" v-b-tooltip="message.seen_by != null">
                                    <i class="material-icons">check</i><i v-if="message.seen_by" class="material-icons">check</i>
                                </span>
                            </small>
                            <b-button v-if="message.file" :href="message.file" target="_blank" variant="warning" size="sm">
                                <i class="material-icons">attach_file</i>
                            </b-button>
                        </template>
                    </b-card>
                </b-col>
            </b-row>
        </b-card>
        <b-card class="send-message-form">
            <b-form @submit.prevent="send">
                <b-row>
                    <b-col :cols="3" :md="2">
                            <b-button-group>
                                <b-button variant="primary" size="sm" type="submit" :disabled="loading || newMessage.length == 0">
                                    <b-spinner v-if="loading" small></b-spinner>
                                    <i v-else class="material-icons">send</i>
                                </b-button>
                                <uppy-widget v-model="newMessageFile" :without-ui="true">
                                    <b-button ref="uppyButton" variant="warning" size="sm" :disabled="loading" :title="sanjabTrans('select_file')" v-b-tooltip>
                                        <i class="material-icons">attach_file</i>
                                    </b-button>
                                </uppy-widget>
                            </b-button-group>
                    </b-col>
                    <b-col :cols="9" :md="10">
                        <b-form-input
                            v-model="newMessage"
                        ></b-form-input>
                    </b-col>
                </b-row>
            </b-form>
        </b-card>
    </div>
</template>

<script>
    export default {
        props: {
            ticket: {
                type: Object,
                default: () => {return {};}
            },
            initialMessages: {
                type: Array,
                default: () => []
            },
        },
        data() {
            return {
                loading: false,
                messages: [],
                newMessage: "",
                newMessageFile: [],
                eventSource: null,
                ajaxTimeoutHandler: null,
            }
        },
        mounted () {
            var self = this;
            this.$sanjabStore.commit('disableNotifications');
            if (this.ticket.messages instanceof Array) {
                this.messages = this.ticket.messages;
            }
            if (typeof Storage !== "undefined") {
                let draftedMessage = localStorage.getItem('sanjab_ticket_draft_message_' + this.ticket.id);
                if (typeof draftedMessage === 'string' && draftedMessage.length > 0) {
                    this.newMessage = draftedMessage;
                }
            }
            setTimeout(function () {
                self.scrollToBottom();
            }, 100);
            this.loadMessages();
            document.addEventListener('visibilitychange', this.changeTypeHandler);
            window.addEventListener('focus', this.changeTypeHandler);
            sanjabBroadcastChannel.addEventListener('message', this.changeTypeHandler);
        },
        destroyed() {
            document.removeEventListener('visibilitychange', this.changeTypeHandler);
            window.removeEventListener('focus', this.changeTypeHandler);
            sanjabBroadcastChannel.addEventListener('message', this.changeTypeHandler);
        },
        methods: {
            loadMessages() {
                var self = this;
                if (typeof sanjabActiveBrowserTabId == 'undefined') {
                    return setTimeout(() => self.loadMessages(), 10);
                }
                sanjabBroadcastChannel.postMessage({type: 'load_ticket_messages'});
                if (sanjabBrowserTabId == sanjabActiveBrowserTabId) {
                    this.loadEventSource();
                } else {
                    this.loadAjax();
                }
            },
            loadEventSource() {
                var self = this;
                if (self.eventSource == null) {
                    this.eventSource = new EventSource(sanjabUrl('/modules/tickets/' + this.ticket.id + '?last_created_at=' + this.lastMessage.created_at));
                    this.eventSource.addEventListener('message', function (event) {
                        if (event.data == 'seen') {
                            for (let i in self.messages) {
                                if (self.messages[i].seen_by == null && self.messages[i].user.id != self.ticket.user.id) {
                                    self.messages[i].seen_by = {id: self.ticket.user.id, name: self.ticket.user.name};
                                }
                            }
                            self.$forceUpdate();
                        } else if (event.data == 'close') {
                            self.eventSource.close();
                            self.eventSource = null;
                            self.loadMessages();
                        } else {
                            let newMessages = JSON.parse(event.data);
                            self.handleNewMessages(newMessages);
                        }
                    }, false);
                }
            },
            loadAjax() {
                var self = this;
                axios.get(sanjabUrl('/modules/tickets/' + this.ticket.id + '?last_created_at=' + this.lastMessage.created_at))
                    .then(function (response) {
                        self.handleNewMessages(response.data);
                    })
                    .catch((e) => console.error(e))
                    .then(function () {
                        if (self.eventSource == null) {
                            self.ajaxTimeoutHandler = setTimeout(() => self.loadAjax(), 10000);
                        }
                    });
            },
            handleNewMessages(newMessages) {
                if (newMessages.length > 0) {
                    let playNotification = false;
                    for (let i in newMessages) {
                        this.messages.push(newMessages[i]);
                        if (newMessages[i].user.id == this.ticket.user.id) {
                            playNotification = true;
                        }
                    }
                    this.messages = this.messages.slice();
                    if (playNotification) {
                        sanjabPlayNotificationSound();
                    }
                    var self = this;
                    setTimeout(function () {
                        self.scrollToBottom();
                    }, 100);
                }
            },
            send() {
                var self = this;
                self.loading = true;
                axios.post(sanjabUrl('modules/tickets/' + this.ticket.id + '/send'), {
                    text: self.newMessage,
                    file: self.newMessageFile.length > 0 ? self.newMessageFile[0] : null
                }).then(function (response) {
                    self.loading = false;
                    self.newMessage = "";
                    self.newMessageFile = [];
                }).catch(function (error) {
                    self.loading = false;
                    console.error(error);
                    if (error.response.status == 422) {
                        sanjabError(Object.values(error.response.data.errors)[0][0]);
                    } else {
                        sanjabHttpError(error.response.status);
                    }
                });
            },
            changeTypeHandler() {
                return setTimeout(() => this.changeType(), 1000);
            },
            changeType() {
                if (this.eventSource && sanjabBrowserTabId != sanjabActiveBrowserTabId) {
                    this.eventSource.close();
                    this.eventSource = null;
                    this.loadMessages();
                }
                if (this.ajaxTimeoutHandler && sanjabBrowserTabId == sanjabActiveBrowserTabId) {
                    clearTimeout(this.ajaxTimeoutHandler);
                    this.ajaxTimeoutHandler = null;
                    this.loadMessages();
                }
            },
            scrollToBottom() {
                $(".messages-container").animate({
                    scrollTop: $('.messages-container')[0].scrollHeight
                });
            }
        },
        computed: {
            lastMessage() {
                if (this.messages.length > 0) {
                    let lastMessage = this.messages[0];
                    for (let i in this.messages) {
                        if (this.messages[i].created_at > lastMessage.created_at) {
                            lastMessage = this.messages[i];
                        }
                    }
                    return lastMessage;
                }
                return null;
            }
        },
        watch: {
            newMessage(newValue, oldValue) {
                if (typeof Storage !== "undefined") {
                    localStorage.setItem('sanjab_ticket_draft_message_' + this.ticket.id, newValue);
                }
            }
        },
    }
</script>

<style lang="scss" scoped>
    .balon-card {
        width: max-content;
        max-width: 90%;
    }

    .messages-container {
        height: 400px;
        overflow: hidden scroll;
    }

    html[dir="ltr"] {
        .balon-card {

            &.balon-card-user {
                border-radius: 40px 40px 0px 40px;
                float: right;

                h4 {
                    text-align: right;
                }
            }

            &:not(.balon-card-user) {
                border-radius: 40px 40px 40px 0px;

                .card-footer small {
                    width: 100%;
                    text-align: left;
                }
            }

            .card-footer {
                .seen {
                    .material-icons:nth-child(2) {
                        margin-left: -17px;
                    }
                }
            }
        }

        .send-message-form {
            input {
                margin-left: 10px;
            }
        }
    }

    html[dir="rtl"] {
        .balon-card {

            &.balon-card-user {
                float: left;
                border-radius: 40px 40px 40px 0px;

                h4 {
                    text-align: left;
                }
            }

            &:not(.balon-card-user) {
                border-radius: 40px 40px 0px 40px;

                .card-footer small {
                    width: 100%;
                    text-align: left;
                }
            }

            .card-footer {
                .seen {
                    .material-icons:nth-child(2) {
                        margin-right: -17px;
                    }
                }
            }
        }

        .send-message-form {
            input {
                margin-right: 10px;
            }
        }
    }
</style>

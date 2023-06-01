<template>
  <div>
    <h1 class="title">Lista wiadomości</h1>
    <div v-if="loading" class="title">Ładowanie...</div>
    <div v-else>
      <!-- Sorotowanie -->
      <div class="sort-select">
        <label for="sort">Sortuj według:</label>
        <select id="sort" v-model="selectedSort" @change="sortMessages">
          <option value="id_asc">ID (rosnąco)</option>
          <option value="id_desc">ID (malejąco)</option>
          <option value="createdAt_asc">Data (rosnąco)</option>
          <option value="createdAt_desc">Data (malejąco)</option>
        </select>
      </div>

      <!-- tabela wiadomosci -->
      <table>
        <tr>
          <th>ID</th>
          <th>Sender</th>
          <th>Created at</th>
          <th>Actions</th>
        </tr>
        <tr v-for="message in messages" :key="message.id">
          <td>{{ message.id }}</td>
          <td>{{ message.sender }}</td>
          <td>{{ message.createdAt }}</td>
          <td>
            <dx-button text="See content" @click="fetchMessageDetails(message.id)" />
          </td>
        </tr>
      </table>
    </div>

    <!-- modal zawartosci wiadomosci/szczegolow wiadomosci -->
    <div class="modal-overlay" v-if="selectedMessage" @click="closeDetailsModal">
      <div class="modal-container">
        <h2>Content of message</h2>
        <div>{{ selectedMessage }}</div>
        <dx-button text="Close" type="danger" @click="closeDetailsModal" />
      </div>
    </div>

    <!-- formularz nowej wiadomosci -->
    <div class="modal-overlay" v-if="showMessageFormVisible" @click="closeMessageForm"></div>
    <div v-if="showMessageFormVisible" class="message-form">
      <h2>Create a new message</h2>
      <form @submit.prevent="addMessage">
        <div>
          <label for="sender">Sender:</label>
          <input type="text" id="sender" v-model="newMessage.sender" required />
        </div>
        <div>
          <label for="content">Content:</label>
          <textarea id="content" v-model="newMessage.content" required></textarea>
        </div>
        <div>
          <dx-button text="Close" type="danger" @click="closeMessageForm" />
          <dx-button text="Add" type="success" :use-submit-behavior="true" />
        </div>
      </form>
    </div>

    <!-- przycisk nowej wiadomosci -->
    <div class="new-message-btn">
      <dx-button text="Create a new message" type="success" @click="showMessageForm" />
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { DxButton } from 'devextreme-vue';

export default {
  name: 'MessageList',
  components: {
    DxButton,
  },
  data() {
    return {
      messages: [],
      loading: false,
      selectedMessage: null,
      showMessageFormVisible: false,
      newMessage: {
        sender: '',
        content: '',
      },
      selectedSort: 'id_asc',
    };
  },
  created() {
    this.fetchMessages();
  },
  methods: {
    fetchMessages() {
      this.loading = true;
      const [sortField, sortOrder] = this.selectedSort.split('_');
      axios
        .get('http://0.0.0.0:8000/api/messages/list', {
          params: {
            sort: sortField,
            order: sortOrder,
          },
        })
        .then(response => {
          this.messages = response.data;
        })
        .catch(error => {
          console.error(error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    sortMessages() {
      this.fetchMessages();
    },
    fetchMessageDetails(messageId) {
      axios
        .get(`http://0.0.0.0:8000/api/messages/read/${messageId}`)
        .then(response => {
          this.selectedMessage = response.data;
        })
        .catch(error => {
          console.error(error);
        });
    },
    closeDetailsModal() {
      this.selectedMessage = null;
    },
    addMessage() {
      axios
        .post('http://0.0.0.0:8000/api/messages/create', this.newMessage)
        .then(response => {
          console.log(response.data);
          this.fetchMessages();
          this.closeMessageForm();
        })
        .catch(error => {
          console.error(error);
        });
    },
    showMessageForm() {
      this.showMessageFormVisible = true;
    },
    closeMessageForm() {
      this.showMessageFormVisible = false;
      this.newMessage.sender = '';
      this.newMessage.content = '';
    },
  },
};
</script>

<style scoped>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-container {
        background-color: #fff;
        padding: 20px;
        border-radius: 4px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th,
    td {
        padding: 8px;
        border-bottom: 1px solid #ddd;
    }
    th {
        background-color: #f2f2f2;
        text-align: left;
    }
    .message-form {
        z-index: 1000;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fff;
        padding: 20px;
        border-radius: 4px;
    }
    .message-form h2 {
        margin-top: 0;
    }
    .message-form form {
        display: flex;
        flex-direction: column;
    }
    .message-form label {
        margin-bottom: 8px;
    }
    .message-form input,
    .message-form textarea {
        padding: 8px;
        margin-bottom: 16px;
    }
    .message-form button {
        margin-top: 8px;
    }
    .new-message-btn {
        margin:20px;
        float:right;
    }
    .sort-select {  
        margin:20px;
        float: right;
    }
    .title {
        text-align: center;
    }
</style>
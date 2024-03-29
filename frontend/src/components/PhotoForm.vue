<template>
  <div v-show="value" class="photo-form">
    <h2 class="title">Submit a photo</h2>
    <div v-show="loading" class="panel">
      <Loader>Sending your photo...</Loader>
    </div>
    <form v-show="!loading" class="form" @submit.prevent="submit">

      <div class="errors" v-if="errors">
        <ul v-if="errors.photo">
          <li v-for="msg in errors.photo" :key="msg">{{ msg }}</li>
        </ul>
      </div>
      <input class="form__item" type="file" @change="onFileChange"
      accept="image/*">
      <output class="form__output" v-if="preview">
        <img :src="preview" alt="">
      </output>

      <div class="form__button">
        <button type="submit" class="button button--inverse">submit</button>
      </div>
    </form>
  </div>
</template>
<!--
表示・非表示の切り替え
ボタンをクリックすると表示され、もう一度押下すると隠れる

v-modelを用いて実装。
v-modelは基本的には入力要素と一緒に用いられるが、他のカスタムコンポーネントでも使用できる
valueをpropsに取って、inputイベントを$emitするコンポーネントにはv-modelを指定できる

Navbar → (props: value) → PhotoForm
PhotoForm → $emit('input', ...) → Navbar

-->

<script>
import axios from 'axios'
import {UNPROCESSABLE_ENTITY} from "@/util"
import Loader from './Loader.vue'

export default {
  components: {
    Loader,
  },

  props: {
    value: {
      type: Boolean,
      required: true,
      default: false,
    }
  },
  // valueを受け取れるようにスクリプトブロックにpropsを追加
  // valueは表示/非表示を審議地で表現するためboolean型と定義する
  // テンプレートの一番外側の要素にv-showの記述を追加する
  // → コンポーネントの表示/非表示を(valueを渡す)親コンポーネント側で制御できるようになる

  data () {
    return {
      loading: false,
      preview: null,
      photo: null,
      errors: null,
    }
  },

  methods: {
    onFileChange (event) {
      // 何も選択されていない場合
      if (event.target.files.length === 0) {
        this.reset()
        return false
      }

      // 画像以外が選択された場合
      if (! event.target.files[0].type.match('image.*')) {
        this.reset()
        return false
      }

      const reader = new FileReader()

      // ファイルの読み込み完了時に実行
      reader.onload = e => {
        // previewに読み込み結果(データURL)を代入する
        // previewに値が入ると、<output>につけたv-ifがtrueと判断される

        // <output>内部の、<img>のsrc属性はpreviewの値を参照しているので、結果として画像が表示される
        this.preview = e.target.result
      }

      // ファイルを読み込む
      // 読み込まれたファイルはデータURL形式で受け取れる(onloadを参照)
      reader.readAsDataURL(event.target.files[0])
      this.photo = event.target.files[0]
    },
    reset () {
      this.preview = ''
      this.photo = null
      this.$el.querySelector('input[type="file"]').value = null
    },

    async submit () {
      this.loading = true

      const formData = new FormData()
      formData.append('photo', this.photo)
      await axios.post('/api/photo', formData)
          .then((response) => {
            this.$router.push(`/photos/${response.data.id}`)
            this.loading = false

            this.$store.commit('message/setContent', {
              content: '写真が投稿されました！',
              timeout: 6000
            })
          })
          .catch((error) => {
            if (error.response.status === UNPROCESSABLE_ENTITY) {
              this.errors = error.response.data.errors

              this.loading = false
              return false
            } else {
              this.reset()
              this.$emit('input', false)

              this.$store.commit('error/setCodes', error.response.status)

              this.loading = false
              return false
            }
          })
    }
  }
}
</script>

<style scoped>

</style>
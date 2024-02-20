export default {
    methods: {
        insertHtml(html){
            const editor = this.$refs.content.$_instance
            editor.model.change( writer => {
                const viewFragment = editor.data.processor.toView(html)
                const modelFragment = editor.data.toModel( viewFragment );
                editor.model.insertContent(modelFragment, editor.model.document.selection)
            })
        },
        insertText(text){
            const editor = this.$refs.content.$_instance
            editor.model.change( writer => {
                const insertPosition = editor.model.document.selection.getFirstPosition()
                writer.insertText(text, insertPosition )
            })
        }
    },
}
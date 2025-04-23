 <!-- Initialize TinyMCE -->
 <!-- Place the first <script> tag in your HTML's <head> -->
 <script src="https://cdn.tiny.cloud/1/kq54asy1zm0lhnt1x2zsqhel46zq48awgwxzw51xfjx1unf9/tinymce/7/tinymce.min.js"
     referrerpolicy="origin">
 </script>

 <!-- Place the following <script> and <textarea> tags your HTML's <body> -->
 <script>
tinymce.init({
    selector: '#content',
    plugins: [
        // Core editing features
        'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists',
        'media',
        'searchreplace', 'table', 'visualblocks', 'wordcount',
        // Your account includes a free trial of TinyMCE premium features
        // Try the most popular premium features until May 7, 2025:
        'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker',
        'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage',
        'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes',
        'mergetags',
        'autocorrect', 'typography', 'inlinecss', 'markdown', 'importword', 'exportword',
        'exportpdf'
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    mergetags_list: [{
            value: 'First.Name',
            title: 'First Name'
        },
        {
            value: 'Email',
            title: 'Email'
        },
    ],
    ai_request: (request, respondWith) => respondWith.string(() => Promise.reject(
        'See docs to implement AI Assistant')),
});
 </script>


 <!-- Content editor -->
 <div>
     <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Post
         Content</label>
     <textarea id="content" name="content" rows="12" class="w-full px-3 py-2 border border-gray-300 rounded-md">
        <?php if(isset($post['content'])){ 
            echo $post['content']; } 
        ?>
        </textarea>
 </div>
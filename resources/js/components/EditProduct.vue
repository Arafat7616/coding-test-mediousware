<template>
    <section>
        <div class="row">
            <div v-if="errors" class="col-md-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul>
                        <li v-for="(err, key) in errors.errors" :key="key">{{ err[0] }}</li>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Product Name</label>
                            <input type="text" v-model="product_name" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Product SKU</label>
                            <input type="text" v-model="product_sku" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Description</label>
                            <textarea v-model="description" id="" cols="30" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                    </div>
                    <div class="card-body border">
                        <vue-dropzone v-on:vdropzone-success="uploadSuccess" v-on:vdropzone-removed-file="removeFile" ref="myVueDropzone" id="dropzone" :options="dropzoneOptions"></vue-dropzone>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" v-for="(item,index) in product_variant" :key="index">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Option</label>
                                    <select v-model="item.option" class="form-control">
                                        <option v-for="(variant, key) in variants" :key="key" :value="variant.id">
                                            {{ variant.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label v-if="product_variant.length != 1" @click="product_variant.splice(index,1); checkVariant"
                                           class="float-right text-primary"
                                           style="cursor: pointer;">Remove</label>
                                    <label v-else for="">.</label>
                                    <input-tag v-model="item.tags" @input="checkVariant" class="form-control"></input-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" v-if="product_variant.length < variants.length && product_variant.length < 3">
                        <button @click="newVariant" class="btn btn-primary">Add another option</button>
                    </div>

                    <div class="card-header text-uppercase">Preview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <td>Variant</td>
                                    <td>Price</td>
                                    <td>Stock</td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(variant_price, key) in product_variant_prices" :key="key">
                                    <td>{{ variant_price.title }}</td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.price">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.stock">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button @click="saveProduct" type="submit" class="btn btn-lg btn-primary">Save</button>
        <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
    </section>
</template>

<script>
import vue2Dropzone from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'
import InputTag from 'vue-input-tag'

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        variants: {
            type: Array,
            required: true
        },
        product: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            product_name: '',
            product_sku: '',
            description: '',
            images: [],
            product_variant: [
                {
                    option: this.variants[0].id,
                    tags: []
                }
            ],
            product_variant_prices: [],
            dropzoneOptions: {
                url: 'https://httpbin.org/post',
                addRemoveLinks: true,
                thumbnailWidth: 150,
                maxFilesize: 0.5,
                headers: {"My-Awesome-Header": "header value"}
            },
            errors: null
        }
    },
    methods: {
        setupComp() {
            this.product_name = this.product.title;
            this.product_sku = this.product.sku;
            this.description = this.product.description;

            this.product.images.forEach(image => {
                const options = { size: this.dropzoneOptions.maxFilesize, name: image.file_path, type: "image/" + image.file_path.split('.')[1] };
                this.$refs.myVueDropzone.manuallyAddFile(options, '/storage/product-images/' + image.file_path);
            });


            let variant_name = "";
            this.product.variants.forEach((item, key) => {
                if (item.variant_one) {
                    if (key === 0) {
                        this.product_variant[0] = { option: item.variant_one.variant_id, tags: [] };
                    }
                    this.product_variant[0].tags.pushIfNotExist(item.variant_one.variant);
                    variant_name = item.variant_one.variant;
                }
                if (item.variant_two) {
                    if (key === 0) {
                        this.product_variant[1] = { option: item.variant_two.variant_id, tags: [] };
                    }
                    this.product_variant[1].tags.pushIfNotExist(item.variant_two.variant);
                    variant_name += `/${item.variant_two.variant}`;
                }
                if (item.variant_three) {
                    if (key === 0) {
                        this.product_variant[2] = { option: item.variant_three.variant_id, tags: [] };
                    }
                    this.product_variant[2].tags.pushIfNotExist(item.variant_three.variant);
                    variant_name += `/${item.variant_three.variant}`;
                }
                

                this.product_variant_prices.push({
                    title: variant_name,
                    price: item.price,
                    stock: item.stock
                })

            });
        },

        // it will push a new object into product variant
        newVariant() {
            let all_variants = this.variants.map(el => el.id)
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(entry1 => !selected_variants.some(entry2 => entry1 == entry2))
            // console.log(available_variants)

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            })
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })

            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                })
            })
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || '';
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function (ans, value) {
                return ans.concat(self.getCombn(arr.slice(1), pre + value + '/'));
            }, []);
            return ans;
        },

        // store product images
        uploadSuccess(file, response) {
            this.images.push(response.files.file);
        },

        removeFile(file, error, xhr) {
            console.log(file.manuallyAdded, error, xhr);
            if (file.manuallyAdded) {
                axios.delete(`/product/image/${file.name}`).catch(error => {
                    this.$swal({
                        icon: 'error',
                        title: 'Error',
                        text: error.response.data.message,
                    });
                });
            }else {
                const index = this.images.indexOf(file.dataURL);
                if (index > -1)
                    this.images.splice(index, 1);
            }
        },

        // store product into database
        saveProduct() {
            this.errors = null;
            let product = {
                title: this.product_name,
                sku: this.product_sku,
                description: this.description,
                product_image: this.images,
                product_variant: this.product_variant,
                product_variant_prices: this.product_variant_prices
            }


            axios.put(`/product/${this.product.id}`, product).then(response => {
                this.$swal({
                    icon: 'success',
                    title: 'Success',
                    text: response.data.message,
                }).then(() => {
                    window.location.href = '/product'
                });

            }).catch(error => {
                this.$swal({
                    icon: 'error',
                    title: 'Error',
                    text: error.response.data.message,
                });

                if (error.response.status === 422)
                    this.errors = error.response.data;
            })

        }


    },
    mounted() {
        this.setupComp();
        console.log('Component mounted.')
    }
}
</script>

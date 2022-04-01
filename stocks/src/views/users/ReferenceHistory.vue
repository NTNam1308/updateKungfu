<template>
  <div>
    <CCard class="mb-1">
      <CCardHeader>
        <CRow>
          <CCol sm="2">
            <CInput
              label="Tên Watchlist"
              v-model="name"
              
            />
          </CCol>
          <CCol sm="2" class="margin-top-1-7rem-t margin-bottom-05rem ">
            <template >
              <CButton color="success" id="addManageCategory" @click="addNewTrading()"
                ><CIcon name="cibAddthis" /> Thêm</CButton
              >
            </template>
            <template >
              <CButton color="primary" id="saveEditCategory" @click="saveEditMyWatchlist()"
                ><CIcon name="cilSave" /> Lưu
              </CButton>
              <CButton
                class="ml-2"
                color="secondary"
                @click="handleButtonCancel"
                >Hủy</CButton
              >
            </template>
          </CCol>
          <CCol sm="8"> </CCol>
          <CCol sm="12">
            <div class="datatable-profit">
              <div class="position-relative table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th
                        class=""
                        style="vertical-align: middle; overflow: hidden"
                      >
                        <div>Tên</div>
                      </th>
                      <th
                        class=""
                        style="vertical-align: middle; overflow: hidden"
                      >
                        <div></div>
                      </th>
                    </tr>
                  </thead>
                  <tbody class="position-relative">
                    <tr v-for="(item, index) in list_item_category" :key="index">
                      <td class="">{{item.name}}</td>
                      <td class="text-center">
                        <CButton
                          size="sm"
                          color="info"
                          class=""
                          @click.prevent="handleButtonEdit(item)"
                        >
                          Sửa
                        </CButton>
                        <CButton
                          size="sm"
                          color="danger"
                          class="ml-1"
                          @click.prevent="showModalDelete(item)"
                        >
                          Xóa
                        </CButton>
                      </td>
                    </tr>
                     <tr v-if="list_item_category.length == 0">
                      <td colspan="9">
                        <div class="text-center my-5">
                          <h2>Chưa có Watchlist nào</h2>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </CCol>
        </CRow>
      </CCardHeader>
    </CCard>
    <!-- <CModal title="Xóa dữ liệu" :show.sync="isShowDeleteModalMC" size="sm">
      <template #footer>
        <CButton color="secondary" @click="isShowDeleteModalMC = false"
          >Đóng</CButton
        >
        <CButton color="danger" id="deleteCategory" @click="deleteMyWatchlist"
          ><CIcon name="cilTrash" /> Xóa
        </CButton>
      </template>
      <CRow>
        <CCol sm="12"
          >Những mã thuộc Watchlist này sẽ bị xóa hết. Bạn có chắc chắn
          không?
        </CCol>
      </CRow>
    </CModal> -->
  </div>
</template>

<script>
import axios from "axios";
import { mapActions } from 'vuex';
export default {
  name: "ManageCategory",
  data() {
    return {
      list_item_category: [],
    }
  },
  created() {
    let self = this;
    self.getAllItemCategory();
    
  },
  methods:{

  },
  mounted() {
    let self = this;
    window.addEventListener('keyup', event => {
      if (event.keyCode === 13) { 
        if(self.isShowDeleteModalMC){
          document.getElementById("deleteCategory").click();
        }
        if(self.action == "edit"){
          document.getElementById("saveEditCategory").click();
        }
        if(self.name != '' && self.action != "edit" ){
           document.getElementById("addManageCategory").click();
        }
      }
    })
  }
};
</script>
<style lang="scss">
.datatable-profit{
  .table {
    td {
      padding: 0.15rem 0.75rem !important;
    }
  }
}
@media screen and (min-width: 768px) {
  .margin-top-1-7rem-t {
    margin-top: 1.8rem;
  }
}
@media screen and (max-width: 768px) {
  .margin-bottom-05rem {
    margin-bottom: 0.5rem;
  }
}



</style>

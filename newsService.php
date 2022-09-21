<?php
namespace module\blog\service;

use model\core\CoreDonViModel;
use module\blog\model\News;
use module\blog\model\NewsCategory;

class newsService extends \lib\core\BaseService {

    public function __construct() {
        parent::__construct();
    }
    
    public function getList() {
        $start = self::getRecordStart();
        $limit = self::getRecordLimit();
        $folderId = self::getIdRequest("cate_id");
        $title = self::getStrRequest("title");
        $data = News::getForGridBySite($start, $limit, $title, $folderId);
        echo json_encode($data);
    }

    public function getListClone() {
        $start = self::getIntRequest('start');
        $limit = self::getIntRequest('limit');
        $siteIdClone = self::getStrRequest('siteIdClone');
        if(empty($siteIdClone)) {
            $siteIdClone = SAMPLE_SITE_ID;
        }
        $data = News::getListClone($start, $limit, $siteIdClone);
        echo json_encode($data);
    }
    
    public function getById() {
        $id = self::getIdRequest('id');
        $data = News::getById($id);

        echo json_encode($data);
    }

    /* @author tuanbv
     * @todo Lấy chi tiết tin tức
     */
    public function getDetailById() {
        $id = self::getIdRequest('id');
        $data = self::db()
            ->select('bn.*, dv.ten as ten_don_vi')
            ->from(blog_news, 'bn')
            ->leftJoin(core_don_vi, 'dv', 'dv.id = bn.don_vi_id')
            ->where('bn.id =:id AND bn.site_id =:siteId')
            ->getRow([
                'id' => $id,
                'siteId' => \phpviet::getSiteId()
            ]);
        echo json_encode($data);
    }

    /* @author tuanbv
     * @todo Xóa tin tức
     */
    public function deleteById() {
        $id = self::getIdRequest('id');
        $r = News::deleteById($id);

        if(empty($r)) {
            $this->error('Xóa tin tức thất bại');
        }
        $this->success('Xóa sự kiện thành công');
    }

    /* @author tuanbv
     * @todo Lấy danh sách tin tức
     */
    public function getListForGrid() {
        $filters = self::getArrRequest('filters');
        $start = !empty($filters['start']) ? $filters['start'] : 0;
        $limit = !empty($filters['limit']) ? $filters['limit'] : 20;
        $title = !empty($filters['title']) ? $filters['title'] : '';
        $folderId = !empty($filters['folder_id']) ? $filters['folder_id'] : '';
        $status = !empty($filters['status']) ? $filters['status'] : '';
        unset($filters['start']);
        unset($filters['limit']);
        unset($filters['title']);
        unset($filters['folder_id']);
        unset($filters['status']);
        $data = News::getListForGrid($start, $limit, $title, $folderId, $filters, $status);
        echo json_encode($data);
    }
    
    public function save() {
        $formData = self::getArrRequest('formData');
        /* @todo validate form data */
        if(empty($formData['title'])) {
            $this->error('Tiêu đề không được để trống');
        }
        if(empty($formData['folderId'])) {
            $this->error('Danh mục tin tức không được để trống');
        }
        // if(empty($formData['donviId'])) {
        //     $this->error('Vui lòng chọn cấp đơn vị');
        // }
        if(empty($formData['description'])) {
            $this->error('Trích dẫn không được để trống');
        }
        if(strlen($formData['description']) > 1000) {
            $this->error('Trích dẫn không được lớn hơn 500 kí tự');
        }

        // if(empty($formData['imageId'])) {
        //     $this->error('Vui lòng chọn ảnh đại diện');
        // }
        
        if(empty($formData['id'])) {
            /* @todo Thêm mới */
            $r = News::insert([
                'don_vi_id' => \phpviet::getCurrentDonviId(),
                'image_id' => $formData['imageId'],
                'category_id' => $formData['folderId'],
                'title' => $formData['title'],
                'description' => $formData['description'],
                'content' => $formData['content'],
                'site_id' => \phpviet::getSiteId(),
                // 'status' => 2,
                'status' => !empty($formData['status']) ? $formData['status'] : 2,
                'deleted' => 1
            ]);
            if(empty($r)) {
                $this->error('Thêm mới tin tức thất bại');
            }
            $this->success('Thêm mới tin tức thành công');
        }
        /* @todo Cập nhật */

        $new = News::getById($formData['id']);
        if(empty($new['id'])) {
            $this->error('Bài viết không tồn tại');
        }

        $r = News::updateById([
            'don_vi_id' => \phpviet::getCurrentDonviId(),
            'image_id' => $formData['imageId'],
            'category_id' => $formData['folderId'],
            'title' => $formData['title'],
            'description' => $formData['description'],
            'content' => $formData['content'],
            'status' => !empty($formData['status']) ? 1 : 2,
            // 'hot' => !empty($formData['hot']) ? $formData['hot'] : 2,
            'deleted' => 1
        ], $formData['id']);
        if(empty($r)) {
            $this->error('Cập nhật tin tức thất bại');
        }
        $this->success('Cập nhật tin tức thành công');
    }

    public function delete()
    {
        $id = self::getIdRequest('data');
        $siteId = \phpviet::getSiteId();
        $oCache = self::CacheMem();
        $data = [
            'deleted' => 1,
        ];
        $deleteNews= \module\blog\model\News::updateById($data, $id);
        if(!$deleteNews) {
            $this->error('Có lỗi xảy ra, không thể xóa tin tức');
        }
        $oCache->del("news-detail-{$siteId}-{$id}");
        $this->success('Đã xóa thành công tin tức');
    }
    
    function to_slug($str) {
        $str = trim(mb_strtolower($str));
        $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
        $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
        $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
        $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
        $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
        $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
        $str = preg_replace('/(đ)/', 'd', $str);
        $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
        $str = preg_replace('/([\s]+)/', '-', $str);
        return $str;
    }

    public function addClone() {
        $id = self::getStrRequest('id');
        $categoryId = self::getStrRequest('category_id');
        if(empty($id)) {
            $this->error('Vui lòng chọn tin tức');
        }
        if(empty($categoryId)) {
            $this->error('Vui lòng chọn danh mục');
        }
        $news = News::getById($id, false);
        $category = NewsCategory::getById($categoryId, true);

        if(empty($news['id'])) {
            $this->error('Bài biết không tồn tại');
        }
        if(empty($category['id'])) {
            $this->error('Danh mục không tồn tại');
        }

        /* @todo Clone tin tức */
        unset($news['id']);
        unset($news['created_date']);
        unset($news['created_user']);
        unset($news['updated_date']);
        unset($news['updated_user']);
        $news['don_vi_id'] = CoreDonViModel::getRootId();
        $news['category_id'] = $category['id'];
        $news['site_id'] = \phpviet::getSiteId();

        $result = News::insert($news);
        if(empty($result)) {
            $this->error('Lỗi kết nối với serve');
        }
        $this->success('Sinh tin tức ' . $news['title'] . ' thành công');
    }

    /**
     * content: change status blog
     * author: tuyentx@vnpt.vn
     * date: 25/07/2022
     * ==========================================
     * return object
     */
    public function chageStatusNew() {
        $status = $this->getIntRequest('status', 0);
        $arrData = $this->getArrRequest('data');
        if (!in_array($status, [1,2]) || empty($arrData['listId'])) {
            $this->error('Sai trạng thái hoặc đối tượng bị rỗng');
        }
        
        $data = self::DBGW()->update('blog_news', ['status' => $status], 'id IN :ids', ['ids' => $arrData['listId']]);

        $this->success('Thành công', $data);
    }

    /**
     * delete blog by id
     */
    public function deleteNewByIds($ids = [])
    {
        $ids = self::getArrRequest('ids');

        $result = 0;
        $delete = 0;
        if (empty($ids)) {
            $this->error('Không có nội dung được chọn');
        } else {
            // xóa bản ghi được chọn
            foreach ($ids as $id) {
                $result = self::DB()->update('blog_news', ['deleted' => '2'], 'id=:id', ['id' => $id]);
                $delete++;
            }
        }

        if (!$result) {
            $this->success('Bạn đã xóa thất bại ' . $delete . ' tin tức!');
        } else {
            $this->success('Bạn đã xóa thành công ' . $delete . ' tin tức!');
        }
    }
}

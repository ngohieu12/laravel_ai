<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Giới thiệu về Laravel 13 - Framework PHP thế hệ mới',
                'slug' => 'gioi-thieu-ve-laravel-13',
                'summary' => 'Laravel 13 ra mắt với nhiều tính năng mới đáng chú ý, bao gồm AI Integration, improved performance và modular architecture.',
                'content' => "Laravel 13 là phiên bản mới nhất của framework PHP phổ biến nhất thế giới. Phiên bản này mang đến nhiều cải tiến đáng kể:\n\n1. **AI Integration**: Tích hợp sẵn AI giúp developer dễ dàng xây dựng ứng dụng thông minh.\n\n2. **Performance Improvements**: Tốc độ xử lý nhanh hơn 40% so với phiên bản trước.\n\n3. **Modular Architecture**: Kiến trúc module linh hoạt, dễ dàng mở rộng.\n\n4. **Built-in Queue System**: Hệ thống hàng đợi mạnh mẽ, hỗ trợ nhiều driver.\n\n5. **Enhanced Security**: Cải thiện bảo mật với CSRF protection và SQL injection prevention.\n\nLaravel 13 thực sự là bước tiến lớn trong phát triển web PHP.",
                'category' => 'cong-nghe',
                'author' => 'Nguyen Van A',
                'is_published' => true,
            ],
            [
                'title' => '10 Meo tang hieu suat lam viec tu xa',
                'slug' => '10-meo-tang-hieu-suat-lam-viec-tu-xa',
                'summary' => 'Nhung meo thuc te giup ban lam viec tu xa hieu qua hon, tu quan ly thoi gian den toi uu khong gian lam viec.',
                'content' => "Lam viec tu xa da tro thanh xu huong khong the dao nguoc. Duoi day la 10 meo giup ban hieu qua hon:\n\n1. **Tao lich trinh co dinh**: Bat dau va ket thuc lam viec dung gio moi ngay.\n\n2. **Toi uu khong gian lam viec**: Dam bao ban ghe thoai mai, anh sang tot.\n\n3. **Su dung Pomodoro Technique**: Lam viec 25 phut, nghi 5 phut.\n\n4. **Tat thong bao khong can thiet**: Tap trung vao cong viec quan trong.\n\n5. **Uong nuoc day du**: Giu co the hydrate de nao hoat dong tot.\n\n6. **Tap the duc deu dan**: Duy tri suc khoe the chat.\n\n7. **Giao tiep ro rang voi team**: Su dung cac cong cu hop tac hieu qua.\n\n8. **Nghi dung luc**: Khong lam viec qua suc.\n\n9. **Hoc ky nang moi**: Lien tuc nang cao trinh do.\n\n10. **Danh gia va dieu chinh**: Thuong xuyen review hieu suat.",
                'category' => 'cuoc-song',
                'author' => 'Tran Thi B',
                'is_published' => true,
            ],
            [
                'title' => 'Huong dan bat dau hoc lap trinh Python cho nguoi moi',
                'slug' => 'huong-dan-hoc-lap-trinh-python',
                'summary' => 'Python la ngon ngu ly tuong cho nguoi moi bat dau. Bai viet huong dan chi tiet tu co ban den du an thuc te.',
                'content' => "Python la ngon ngu lap trinh de hoc nhat hien nay. Duoi day la lo trinh cho nguoi moi:\n\n**Giai doan 1: Cu phap co ban**\n- Variables va data types\n- Loops va conditionals\n- Functions\n- Lists va dictionaries\n\n**Giai doan 2: OOP**\n- Classes va Objects\n- Inheritance\n- Polymorphism\n\n**Giai doan 3: Libraries pho bien**\n- NumPy (toan hoc)\n- Pandas (xu ly du lieu)\n- Matplotlib (truc quan hoa)\n\n**Giai doan 4: Du an thuc te**\n- Web scraper\n- Chatbot don gian\n- Game 2D\n\nBat dau ngay hom nay voi Python!",
                'category' => 'hoc-tap',
                'author' => 'Le Minh C',
                'is_published' => true,
            ],
            [
                'title' => 'Xu huong cong nghe 2026: AI va IoT thay doi cuoc song',
                'slug' => 'xu-huong-cong-nghe-2026',
                'summary' => 'Kham pha nhung xu huong cong nghe se dinh hinh tuong lai trong nam 2026 va cach chung anh huong den doi song hang ngay.',
                'content' => "Nam 2026 chung kien su bung no cua nhieu xu huong cong nghe:\n\n**1. AI Everywhere**\nTri tue nhan tao hien dien trong moi aspect cua cuoc song: tu smartphone den xe hoi, tu y te den giao duc.\n\n**2. IoT Smart Home**\nNha thong minh tro nen pho bien voi hang ty thiet bi ket noi internet.\n\n**3. Cloud Computing**\nDien toan dam may tiep tuc phat trien manh voi cac dich vu AI-as-a-Service.\n\n**4. Cybersecurity**\nBao mat mang tro thanh uu ti hang dau khi nhieu thiet bi ket noi.\n\n**5. Sustainable Tech**\nCong nghe xanh, nang luong tai tao duoc day manh.\n\n**6. AR/VR**\nThuc te ao mo rong ap dung trong giao duc va giai tri.\n\n**7. Blockchain**\nBlockchain vuot ra ngoai crypto, ap dung trong quan ly chuoi cung ung.\n\nTuong lai da den!",
                'category' => 'cong-nghe',
                'author' => 'Nguyen Van A',
                'is_published' => true,
            ],
            [
                'title' => 'Cach xay dung thoi quen doc sach hieu qua',
                'slug' => 'cach-xay-dung-thoi-quen-doc-sach',
                'summary' => 'Doc sach la thoi quen quy gia. Bai viet chia se phuong phap giup ban duy tri viec doc sach moi ngay.',
                'content' => "Doc sach la mot trong nhung thoi quen tot nhat cho su phat trien ban than. Duoi day la cach xay dung thoi quen:\n\n**Buoc 1: Dat muc tieu thuc te**\nBat dau voi 15 phut moi ngay, sau do tang dan.\n\n**Buoc 2: Chon sach phu hop**\nChon the loai ban yeu thich, khong ep buoc.\n\n**Buoc 3: Tao moi truong ly tuong**\nTim goc doc yen tinh, thoai mai.\n\n**Buoc 4: Mang theo sach moi luc**\nSach dien tu tren dien thoai giup ban doc moi luc.\n\n**Buoc 5: Ghi chep va chia se**\nViet tom tat, chia se voi ban be.\n\n**Buoc 6: Tham gia cong dong**\nTham gia cau lac bo sach de co dong luc.\n\n**Meo nho:** Doc 20 trang moi ngay = 30 cuon sach moi nam!\n\nHaque dau ngay!",
                'category' => 'hoc-tap',
                'author' => 'Le Minh C',
                'is_published' => true,
            ],
            [
                'title' => 'Nhung cong cu AI mien phi giup tang nang suat lam viec',
                'slug' => 'cong-cu-ai-mien-phi-tang-nang-suat',
                'summary' => 'Tong hop cac cong cu AI mien phi hoac gia re giup ban lam viec hieu qua hon trong nam 2026.',
                'content' => "AI dang tro thanh tro thu dac luc cho moi nguoi. Duoi day la cac cong cu mien phi:\n\n**1. ChatGPT (Free tier)**\nTro ly AI da nang, ho tro viet lach, code, phan tich.\n\n**2. Google Gemini**\nAI cua Google tich hop trong Workspace, ho tro email, tai lieu.\n\n**3. Notion AI**\nQuan ly du an ket hop AI, giup tom tat tai lieu.\n\n**4. Canva AI**\nThiet ke do hoa voi AI, tao hinh anh tu van ban.\n\n**5. GitHub Copilot (Free)**\nHo tro lap trinh vien voi goi y code thong minh.\n\n**6. Perplexity AI**\nCong cu tim kiem AI, tra loi cau hoi co nguon trich dan.\n\n**7. Claude (Free tier)**\nAI assistant cua Anthropic, thich hop phan tich va viet.\n\n**Meo:** Ket hop nhieu cong cu de toi uu hieu qua!",
                'category' => 'cong-nghe',
                'author' => 'Tran Thi B',
                'is_published' => true,
            ],
            [
                'title' => 'Che do dinh duong lanh manh cho dan van phong',
                'slug' => 'che-do-dinh-duong-cho-dan-van-phong',
                'summary' => 'Loi khuyen ve che do an uong giup dan van phong duy tri suc khoe va nang suat suot ngay dai.',
                'content' => "Dan van phong thuong doi mat voi nhieu van de suc khoe do che do an uong khong hop ly. Duoi day la loi khuyen:\n\n**Bua sang**\n- Khong bo bua sang\n- Uu tien protein: trung, sua chua, yen mach\n- Tranh do an nhanh\n\n**Bua trua**\n- An nhieu rau xanh\n- Han che com trang, uu tien gao lut\n- Uong nuoc truoc khi an\n\n**Bua xe**\n- Snack lanh manh: hat, trai cay\n- Tranh do ngot, nuoc co ga\n\n**Bua toi**\n- An nhe, truoc 7 gio toi\n- Tranh an khuya\n\n**Uong nuoc**\n- Uong it nhat 2 lit nuoc moi ngay\n- Co the them chanh, bac ha\n\n**Thuc pham nen tranh**\n- Do an chien nhieu dau\n- Thuc pham che bien san\n- Do ngot qua muc\n\nHaque dau thay doi tu hom nay!",
                'category' => 'cuoc-song',
                'author' => 'Le Minh C',
                'is_published' => true,
            ],
            [
                'title' => 'Ban nhap: Meo chup anh dep bang dien thoai',
                'slug' => 'meo-chup-anh-dep-bang-dien-thoai',
                'summary' => 'Nhung tips chup anh dep bang smartphone ma khong can thiet bi chuyen nghiep.',
                'content' => "Dien thoai ngay nay co camera rat tot. Duoi day la cach chup anh dep:\n\n1. **Anh sang tu nhien**: Chup gan cua so hoac ngoai troi.\n\n2. **Clean lens**: Ve sinh ong kinh truoc khi chup.\n\n3. **Composition**: Su dung rule of thirds.\n\n4. **Steady hand**: Giu tay chac chan.\n\n5. **Edit photos**: Su dung apps nhu Snapseed, Lightroom.\n\nDay la ban nhap, chua xuat ban.",
                'category' => 'doi-song',
                'author' => 'Admin',
                'is_published' => false,
            ],
        ];

        foreach ($posts as $post) {
            Post::create($post);
        }
    }
}

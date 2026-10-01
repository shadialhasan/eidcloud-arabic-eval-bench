[🇸🇦 العربية](README.ar.md) | [🇬🇧 English](README.md)

# eidcloud-arabic-eval-bench | المنصة المعيارية لتقييم واختبار فهم نماذج الذكاء الاصطناعي للغة العربية

[![الإصدار](https://img.shields.io/badge/version-v1.0.0-blue.svg)](https://github.com/shadialhasan/eidcloud-arabic-eval-bench/releases)
[![بيئة التشغيل](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://www.php.net)
[![الترخيص: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![فتح في كولاب](https://colab.research.google.com/assets/colab-badge.svg)](https://colab.research.google.com/github/shadialhasan/eidcloud-arabic-eval-bench/blob/main/notebooks/quickstart.ipynb)
[![المنظومة](https://img.shields.io/badge/EIDCloud-Ecosystem-green.svg)](https://github.com/shadialhasan/eidcloud-cli)

مجموعة اختبارات معيارية مصممة خصيصاً لقياس قدرات نماذج اللغة في النحو العربي، استخراج صيغ الـ JSON، وتلخيص النصوص دون تحريف.


> **☁️ إشعار المنظومة:** هذه الأداة هي جزء مستقل من منظومة **[EIDCloud](https://github.com/shadialhasan/eidcloud-cli)**.  
> يمكنك استخدامها بمفردها كأداة متخصصة، أو إدارتها مع كافة أدوات المنظومة الـ 49 عبر الماستر CLI المركزي: `eidcloud`.


---

## 📑 التصنيف والقطاع
**معالجة اللغة العربية وذكاء النصوص (Arabic NLP)**

---

## ✨ المميزات الرئيسية

- **بنك أسئلة معياري يغطي الدلالات، الصرف، وفهم السياق العربي المعقد**
- **قياس دقة استخراج كائنات JSON العربية والالتزام بالمخططات المحددة**
- **حساب معدلات الدقة والسرعة وتكلفة التوكنز لكل نموذج على حدة**
- **مقارنة بصرية بين أداء النماذج المفتوحة والنماذج السحابية**

---

## 🚀 التثبيت والتشغيل السريع

### 1. الاستخدام المستقل (Standalone)
يمكنك استنساخ وتشغيل هذه الأداة بشكل منفصل تماماً:

```bash
git clone https://github.com/shadialhasan/eidcloud-arabic-eval-bench.git
cd eidcloud-arabic-eval-bench
php tests/run_tests.php
```

### 2. التثبيت كجزء من الماستر CLI الموحد
إذا كان لديك أداة `eidcloud-cli` مثبتة، يمكنك تسجيل هذه الأداة أو تثبيتها بأمر واحد:

```bash
eidcloud plugin add https://github.com/shadialhasan/eidcloud-arabic-eval-bench.git
```

---

## 💻 أمثلة الاستخدام عبر سطر الأوامر

```bash
# تشغيل الأداة مباشرة
php bin/eidcloud-ar-eval run --model=ollama/qwen:7b --suite=syntax
```

```bash
# عرض المساعدة والدليل الشامل
php bin/eidcloud-ar-eval --help
```

---

## 🧪 الاختبارات الآلية

تم تجهيز هذا المستودع بمجموعة اختبارات ذاتية متكاملة تعمل بدون أي مكتبات خارجية:

```bash
php tests/run_tests.php
```

---

## 🌐 تجربة فورية عبر المتصفح (Google Colab)

يمكنك تجربة الأداة مباشرة على سحابة Google بدون الحاجة لتثبيت أي متطلبات محلياً:  
[![Open In Colab](https://colab.research.google.com/assets/colab-badge.svg)](https://colab.research.google.com/github/shadialhasan/eidcloud-arabic-eval-bench/blob/main/notebooks/quickstart.ipynb)

---

## 👤 المؤلف والمشرف

**م. محمد شادي الحسن**  
- **الدور:** المدير التقني التنفيذي ومهندس الحلول المؤسسية  
- **البريد الإلكتروني:** [mhd.shadi.alhasan@gmail.com](mailto:mhd.shadi.alhasan@gmail.com)  
- **الهاتف / واتساب:** [+963934005922](tel:+963934005922)  
- **الموقع:** دمشق، سوريا  
- **GitHub:** [shadialhasan](https://github.com/shadialhasan)  

---

## 📄 الرخصة

هذا المشروع مرخص بموجب رخصة MIT - انظر ملف [LICENSE](LICENSE) للتفاصيل.  
حقوق النشر (c) 2026 **م. محمد شادي الحسن**. جميع الحقوق محفوظة.

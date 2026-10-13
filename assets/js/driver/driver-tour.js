// assets/js/driver/driver-tour.js

const tourTranslations = {
    ar: {
        nextBtn: "التالي",
        prevBtn: "السابق",
        doneBtn: "إنهاء",
        steps: [
            { element: '#username', title: '🚀 اسم المستخدم', description: 'أدخل اسماً مميزاً لحسابك ليتمكن زملاؤك من التعرف عليك.' },
            { element: '#email', title: '📧 البريد الإلكتروني', description: 'استخدم بريدك الإلكتروني للتواصل واسترجاع الحساب متى شئت.' },
            { element: '#gender', title: '👤 الجنس', description: 'حدد جنسك لإكمال ملفك الشخصي كطالب أو طالبة.' },
            { element: '#password', title: '🔒 كلمة المرور', description: 'اختر كلمة مرور قوية (8 أحرف على الأقل). يمكنك استخدام أيقونة العين لمعاينتها.' },
            { element: '#profile_picture', title: '📸 الصورة الشخصية', description: 'يمكنك إبراز هويتك برفع صورة شخصية لحسابك (هذه الخطوة اختيارية).' },
            { element: '#division', title: '📚 الشعبة الدراسية', description: 'اختر شعبتك بدقة لعرض المقررات والفصول المتاحة لك خلال هذا العام الجامعي.' },
            { element: '#add-enrollment-btn', title: '➕ إضافة مقرر', description: 'انقر هنا لإضافة مقرر دراسي آخر إلى قائمتك (بحد أقصى مقررين).' },
            { element: 'button[type="submit"]', title: '✨ إنشاء الحساب', description: 'بعد التأكد من جميع بياناتك، انقر هنا لبدء رحلتك في عالم المعرفة!' }
        ]
    },
    fr: {
        nextBtn: "Suivant",
        prevBtn: "Précédent",
        doneBtn: "Terminer",
        steps: [
            { element: '#username', title: '🚀 Nom d\'utilisateur', description: 'Saisissez un nom unique pour votre compte.' },
            { element: '#email', title: '📧 Email', description: 'Utilisez votre adresse email pour la communication.' },
            { element: '#gender', title: '👤 Genre', description: 'Sélectionnez votre genre pour compléter votre profil.' },
            { element: '#password', title: '🔒 Mot de passe', description: 'Choisissez un mot de passe fort (au moins 8 caractères).' },
            { element: '#profile_picture', title: '📸 Photo de profil', description: 'Vous pouvez télécharger une photo de profil (optionnel).' },
            { element: '#division', title: '📚 Filière', description: 'Choisissez votre filière pour voir les cours disponibles.' },
            { element: '#add-enrollment-btn', title: '➕ Ajouter un cours', description: 'Cliquez ici pour ajouter un autre cours (maximum 2).' },
            { element: 'button[type="submit"]', title: '✨ Créer le compte', description: 'Cliquez ici pour finaliser votre inscription.' }
        ]
    },
    en: {
        nextBtn: "Next",
        prevBtn: "Previous",
        doneBtn: "Done",
        steps: [
            { element: '#username', title: '🚀 Username', description: 'Enter a unique name for your account.' },
            { element: '#email', title: '📧 Email', description: 'Use your email for communication and account recovery.' },
            { element: '#gender', title: '👤 Gender', description: 'Select your gender to complete your profile.' },
            { element: '#password', title: '🔒 Password', description: 'Choose a strong password (at least 8 characters).' },
            { element: '#profile_picture', title: '📸 Profile Picture', description: 'You can upload a profile picture (optional).' },
            { element: '#division', title: '📚 Division', description: 'Choose your division to see available courses.' },
            { element: '#add-enrollment-btn', title: '➕ Add Course', description: 'Click here to add another course (maximum 2).' },
            { element: 'button[type="submit"]', title: '✨ Create Account', description: 'Click here to finalize your registration.' }
        ]
    }
};

const urlParams = new URLSearchParams(window.location.search);
const currentLang = window.appLang || urlParams.get('lang') || localStorage.getItem('lang') || 'ar';
const langData = tourTranslations[currentLang] || tourTranslations['ar'];

document.addEventListener('DOMContentLoaded', function() {
    if (window.driver) {
        const driver = window.driver.js.driver;
        const tour = driver({
            showProgress: true,
            popoverClass: 'mo7adaraty-tour',
            nextBtnText: langData.nextBtn,
            prevBtnText: langData.prevBtn,
            doneBtnText: langData.doneBtn,
            allowClose: true,
            steps: langData.steps.map(step => ({
                element: step.element,
                popover: {
                    title: step.title,
                    description: step.description,
                    side: "bottom",
                    align: 'start'
                }
            }))
        });

        setTimeout(() => {
            tour.drive();
        }, 1000); 

    }
});
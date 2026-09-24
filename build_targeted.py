from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Paragraph

outdoc=r"C:\Users\shpctac10605\Desktop\Beyond_OS\Founder_Full_Stack_Resume.docx"
outpdf=r"C:\Users\shpctac10605\Desktop\Beyond_OS\Founder_Full_Stack_Resume.pdf"
black=RGBColor(0,0,0)
d=Document(); s=d.sections[0]; s.top_margin=Inches(.52); s.bottom_margin=Inches(.52); s.left_margin=Inches(.68); s.right_margin=Inches(.68)
d.styles["Normal"].font.name="Arial"; d.styles["Normal"].font.size=Pt(9.3); d.styles["Normal"].font.color.rgb=black; d.styles["Normal"].paragraph_format.space_after=Pt(2)
def add(text="",size=9.3,bold=False,align=None,after=2,before=0):
    p=d.add_paragraph(); p.paragraph_format.space_after=Pt(after); p.paragraph_format.space_before=Pt(before)
    if align is not None: p.alignment=align
    r=p.add_run(text); r.font.name="Arial"; r.font.size=Pt(size); r.font.color.rgb=black; r.bold=bold
    return p
def heading(text):
    p=add(text.upper(),10.2,True,after=3,before=7); p.paragraph_format.keep_with_next=True
def position(title,org,date):
    p=add("",10,True,after=1,before=2); p.paragraph_format.keep_with_next=True
    r=p.add_run(title); r.font.color.rgb=black
    r=p.add_run("    "+date); r.bold=False; r.font.color.rgb=black
    p.paragraph_format.tab_stops.add_tab_stop(Inches(6.0))
    # Use right-aligned date via tab character
    p.clear(); p.add_run(title); p.add_run("\t"+date)
    p.paragraph_format.tab_stops.add_tab_stop(Inches(6.25))
    q=add(org,8.7,False,after=2); q.paragraph_format.keep_with_next=True
def bullet(text):
    p=add("",9.1,False,after=1); p.paragraph_format.left_indent=Inches(.18); p.paragraph_format.first_line_indent=Inches(-.12); p.add_run("• "+text)
add("GRÉGOIRE J. ROSIER",18,True,WD_ALIGN_PARAGRAPH.CENTER,1)
add("FOUNDER & FULL-STACK LINUX (LAMP) | NATIVE JAVA / SWIFT MOBILE DEVELOPER",8.8,True,WD_ALIGN_PARAGRAPH.CENTER,2)
add("Nanaimo, BC | (250) 667-5487 | rosiergreg@gmail.com",8.2,False,WD_ALIGN_PARAGRAPH.CENTER,1)
add("Portfolio: beyondimagination.co.technology | GitHub: github.com/BeyondImaginationTechnology",8.2,False,WD_ALIGN_PARAGRAPH.CENTER,7)
add("Full-stack product builder targeting junior web, front-end, software engineering, and Linux-focused development roles.",9.3,True,WD_ALIGN_PARAGRAPH.CENTER,7)
heading("Highlights of Qualifications")
for x in ["Founder and hands-on developer with a decade of experience building, deploying, and supporting web products from concept through release.","Full-stack experience with Linux hosting, Apache, PHP, MySQL, JavaScript, HTML5, CSS3, JSON, REST APIs, responsive interfaces, and Git-based workflows.","Built BIT OS, a connected product ecosystem with identity management, user profiles, application access, media, education, wellness, and commerce capabilities.","Native mobile development foundation in Java and Swift, supported by a Graduate Diploma in Mobile App Design and Development.","Experienced integrating authentication, payments, email, AI-generation, text-to-speech, and other third-party APIs; comfortable debugging production, hosting, quota, and deployment issues.","Strong product mindset with practical experience in UX design, admin tools, dashboards, QA, technical documentation, multilingual content, and live-site troubleshooting."]: bullet(x)
heading("Employment Experience")
position("Founder & Full-Stack Developer","Beyond Imagination Corp. Technology","2019 - Present")
add("Nanaimo, BC",8.7,False,after=2)
for x in ["Designed and developed BIT OS, a connected web ecosystem with shared identity (os.beyondimagination.co.technology), user profiles, application access, and a unified rewards concept.","Built responsive PHP, MySQL, and JavaScript applications featuring authentication, dashboards, admin tools, dynamic forms, content libraries, and multilingual experiences.","Integrated payment, authentication, email, AI-generation, and text-to-speech services while resolving API, hosting, quota, and production issues.","Led product strategy, UX design, Git workflows, testing, documentation, deployment, and live-site troubleshooting from concept through release."]: bullet(x)
heading("Selected Technical Projects")
position("BIT OS & Beyond ID","Shared identity and application ecosystem","")
for x in ["Account registration, verification, profiles, and cross-product application access.","Designed the foundation for connected products and a unified rewards concept."]: bullet(x)
position("Beyond TV","Mobile-first streaming platform","")
for x in ["Scheduled programming, program guides, content rotation, playback fallbacks, and responsive interfaces."]: bullet(x)
position("Daily Breath & Beyond French","Faith and language-learning applications","")
for x in ["Reusable content templates, multilingual narration, lesson libraries, and publishing tools."]: bullet(x)
position("Beyond Tattoo","Creator platform","")
for x in ["Automated stencil generation, premium collections, download tracking, administrative workflows, and print-ready assets."]: bullet(x)
heading("Additional Experience")
for x in ["Budget Car Rental - Lot Attendant / Car Wash Associate | 2025","COBS Bread Bakery - Junior Baker | 2025","Home Depot - Merchandise Execution Team Associate | 2025","Sobeys - Delivery Driver | 2022","Bell (The Source) - Key Holder / Sales Associate | 2018 - 2019","Winners / HomeSense - Sales Associate | 2014"]: add(x,8.8)
heading("Education & Credentials")
add("Graduate Diploma | Algonquin College - Mobile App Design & Development, Ottawa, ON",8.8)
add("High School Diploma | Franco-Cité École Secondaire Catholique, Ottawa, ON",8.8)
add("Sell It Right BC Certification",8.8,True)
d.save(outdoc)

# PDF
styles=getSampleStyleSheet(); base=ParagraphStyle("base",parent=styles["Normal"],fontName="Helvetica",fontSize=8.6,leading=10.1,textColor="black",spaceAfter=2); h=ParagraphStyle("h",parent=base,fontName="Helvetica-Bold",fontSize=10.2,leading=11.5,spaceBefore=7,spaceAfter=3); ttl=ParagraphStyle("ttl",parent=base,fontName="Helvetica-Bold",fontSize=18,leading=19,alignment=TA_CENTER,spaceAfter=1); sub=ParagraphStyle("sub",parent=base,fontName="Helvetica-Bold",fontSize=8.3,leading=9,alignment=TA_CENTER,spaceAfter=2); ct=ParagraphStyle("ct",parent=base,fontSize=7.4,leading=8.6,alignment=TA_CENTER,spaceAfter=6); centerbold=ParagraphStyle("centerbold",parent=base,fontName="Helvetica-Bold",fontSize=8.7,leading=10,alignment=TA_CENTER,spaceAfter=6); rstyle=ParagraphStyle("rstyle",parent=base,fontName="Helvetica-Bold",fontSize=9.3,leading=10.2,spaceBefore=2,spaceAfter=1); meta=ParagraphStyle("meta",parent=base,fontSize=8.1,leading=9,spaceAfter=2)
pdf=SimpleDocTemplate(outpdf,pagesize=letter,rightMargin=.68*inch,leftMargin=.68*inch,topMargin=.52*inch,bottomMargin=.52*inch)
story=[Paragraph("GRÉGOIRE J. ROSIER",ttl),Paragraph("FOUNDER & FULL-STACK LINUX (LAMP) | NATIVE JAVA / SWIFT MOBILE DEVELOPER",sub),Paragraph("Nanaimo, BC | (250) 667-5487 | rosiergreg@gmail.com<br/>Portfolio: beyondimagination.co.technology | GitHub: github.com/BeyondImaginationTechnology",ct),Paragraph("Full-stack product builder targeting junior web, front-end, software engineering, and Linux-focused development roles.",centerbold)]
def H(x): story.append(Paragraph(x.upper(),h))
def P(x,sty=base): story.append(Paragraph(x,sty))
def POS(a,b,c=""): P("<b>"+a+"</b>"+(("    "+c) if c else ""),rstyle); P(b,meta)
def B(x): P("&bull; "+x)
H("Highlights of Qualifications")
for x in ["Founder and hands-on developer with a decade of experience building, deploying, and supporting web products from concept through release.","Full-stack experience with Linux hosting, Apache, PHP, MySQL, JavaScript, HTML5, CSS3, JSON, REST APIs, responsive interfaces, and Git-based workflows.","Built BIT OS, a connected product ecosystem with identity management, user profiles, application access, media, education, wellness, and commerce capabilities.","Native mobile development foundation in Java and Swift, supported by a Graduate Diploma in Mobile App Design and Development.","Experienced integrating authentication, payments, email, AI-generation, text-to-speech, and other third-party APIs; comfortable debugging production, hosting, quota, and deployment issues.","Strong product mindset with practical experience in UX design, admin tools, dashboards, QA, technical documentation, multilingual content, and live-site troubleshooting."]: B(x)
H("Employment Experience"); POS("Founder & Full-Stack Developer","Beyond Imagination Corp. Technology","2019 - Present"); P("Nanaimo, BC",meta)
for x in ["Designed and developed BIT OS, a connected web ecosystem with shared identity (os.beyondimagination.co.technology), user profiles, application access, and a unified rewards concept.","Built responsive PHP, MySQL, and JavaScript applications featuring authentication, dashboards, admin tools, dynamic forms, content libraries, and multilingual experiences.","Integrated payment, authentication, email, AI-generation, and text-to-speech services while resolving API, hosting, quota, and production issues.","Led product strategy, UX design, Git workflows, testing, documentation, deployment, and live-site troubleshooting from concept through release."]: B(x)
H("Selected Technical Projects")
for a,b,c in [("BIT OS & Beyond ID","Shared identity and application ecosystem","Account registration, verification, profiles, and cross-product application access. Designed the foundation for connected products and a unified rewards concept."),("Beyond TV","Mobile-first streaming platform","Scheduled programming, program guides, content rotation, playback fallbacks, and responsive interfaces."),("Daily Breath & Beyond French","Faith and language-learning applications","Reusable content templates, multilingual narration, lesson libraries, and publishing tools."),("Beyond Tattoo","Creator platform","Automated stencil generation, premium collections, download tracking, administrative workflows, and print-ready assets.")]:
    POS(a,b); B(c)
H("Additional Experience")
for x in ["Budget Car Rental - Lot Attendant / Car Wash Associate | 2025","COBS Bread Bakery - Junior Baker | 2025","Home Depot - Merchandise Execution Team Associate | 2025","Sobeys - Delivery Driver | 2022","Bell (The Source) - Key Holder / Sales Associate | 2018 - 2019","Winners / HomeSense - Sales Associate | 2014"]: P(x)
H("Education & Credentials")
for x in ["Graduate Diploma | Algonquin College - Mobile App Design & Development, Ottawa, ON","High School Diploma | Franco-Cité École Secondaire Catholique, Ottawa, ON","<b>Sell It Right BC Certification</b>"]: P(x)
pdf.build(story)
print(outdoc+"\n"+outpdf)

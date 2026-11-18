Attribute VB_Name = "modKhaiThueTuDongIJKLv7"
Option Explicit

' ================================================================
' BO VBA DUNG LAI CHO CAC THANG
' 1) ChayTatCaKhaiThue: backup + doi chieu + bung chi tiet + dien o trong
'    + lam tron K/L + hau kiem + ghi NHAT_KY_VBA.
' 2) LamTronSoLuongVaDonGia: chi lam tron K va L, giu nguyen M/O.
' 3) KiemTraToanBoKhaiThue: chi kiem tra va ghi nhat ky.
'
' Co the import module nay vao tung file .xlsm, hoac luu trong PERSONAL.XLSB.
' Truoc khi chay, phai kich hoat dung workbook du lieu va workbook da duoc luu.
' Module da cap nhat cho bo cot nguon moi. Exact 1-1 dung khoa day du;
' sheet "tong hop hoa don" la nguon du phong va xac nhan cac nhom con lai.
' Cot Ten hang hoa/dich vu uu tien Dien giai: chi tiet W -> tong hop W -> goc.
' O trong Don vi tinh/So luong/Don gia uu tien chi tiet -> tong hop.
' ================================================================

' ===== CAU HINH THEO FILE DEMO =====
Private Const KHAI_SHEET_1 As String = "KHAI THUE"
Private Const KHAI_SHEET_2 As String = "khai thue"
Private Const DETAIL_SHEET_1 As String = "chi tiet hoa don"
Private Const DETAIL_SHEET_2 As String = "hoa don chi tiet"
Private Const SUMMARY_SHEET_1 As String = "tong hop hoa don"
Private Const SUMMARY_SHEET_2 As String = "tong hop hoa don"
Private FIRST_KHAI_ROW As Long
Private FIRST_DETAIL_ROW As Long
Private FIRST_SUMMARY_ROW As Long
Private Const LOG_SHEET_NAME As String = "NHAT_KY_VBA"
Private Const ISSUE_SHEET_NAME As String = "NHAT_KY_DOI_CHIEU"
Private Const MAX_ISSUE_LOG_ROWS As Long = 500
Private Const QTY_DECIMALS As Long = 2
Private Const PRICE_DECIMALS As Long = 0

' True: cho phep doi chieu khi chi sai Ky hieu, nhung Mau so + So HD + Ngay + MST
' va tong tien/thue phai khop duy nhat.
Private Const ALLOW_UNIQUE_SERIES_FALLBACK As Boolean = True
Private Const CREATE_BACKUP_COPY As Boolean = True

' Sai so doi chieu do lam tron VND.
Private Const AMOUNT_TOLERANCE As Double = 1#
Private Const VAT_TOLERANCE As Double = 1.1

' Cot sheet KHAI THUE - duoc gan lai tu tieu de moi lan chay.
Private K_COL_MODEL As Long
Private K_COL_SERIES As Long
Private K_COL_NUMBER As Long
Private K_COL_DATE As Long
Private K_COL_VENDOR As Long
Private K_COL_TAX_ID As Long
Private K_COL_ITEM As Long
Private K_COL_UNIT As Long
Private K_COL_QTY As Long
Private K_COL_PRICE As Long
Private K_COL_AMOUNT As Long
Private K_COL_RATE As Long
Private K_COL_VAT As Long

' Cot sheet chi tiet hoa don sau khi bo sung cot thong tin cong ty/nguoi mua.
Private D_COL_MODEL As Long
Private D_COL_SERIES As Long
Private D_COL_NUMBER As Long
Private D_COL_DATE As Long
Private D_COL_VENDOR As Long
Private D_COL_TAX_ID As Long
Private D_COL_ITEM As Long
Private D_COL_UNIT As Long
Private D_COL_QTY As Long
Private D_COL_PRICE As Long
Private D_COL_TOTAL_GOODS As Long
Private D_COL_PRETAX As Long
Private D_COL_RATE As Long
Private D_COL_VAT As Long

' Cot sheet tong hop hoa don. Sheet nay dung de xac nhan khoa/tong, lam
' nguon dien giai uu tien 2 va nguon du phong cho J/K/L khi chi tiet bi thieu.
Private S_COL_MODEL As Long
Private S_COL_SERIES As Long
Private S_COL_NUMBER As Long
Private S_COL_DATE As Long
Private S_COL_VENDOR As Long
Private S_COL_TAX_ID As Long
Private S_COL_ITEM As Long
Private S_COL_UNIT As Long
Private S_COL_QTY As Long
Private S_COL_PRICE As Long
Private S_COL_TOTAL_GOODS As Long
Private S_COL_PRETAX As Long
Private S_COL_RATE As Long
Private S_COL_VAT As Long

' Macro chinh de gan vao nut bam. Co the dung lai cho moi workbook thang sau.
Public Sub ChayTatCaKhaiThue()
    CapNhatKhaiThueTuChiTiet
End Sub

' Chi dien cac o trong cua 4 cot I:J:K:L.
' Uu tien chi tiet hoa don; neu chi tiet khong co gia tri moi dung tong hop.
' Chi xu ly hoa don doi chieu dung 1 dong KHAI THUE - 1 dong nguon.
' Khong ghi de du lieu dang co hoac cong thuc.
Public Sub CapNhatBonCotTrong_IJKL()
    Dim wb As Workbook
    Dim wsK As Worksheet, wsD As Worksheet, wsS As Worksheet
    Dim khaiGroups As Object, detailGroups As Object, summaryGroups As Object
    Dim noResolvedKeys As Object, noBlockedKeys As Object, stats As Object
    Dim lastK As Long, lastD As Long, lastS As Long
    Dim r As Long, sourceKey As String
    Dim cellsFilled As Long
    Dim backupPath As String, errorText As String
    Dim oldCalculation As XlCalculation
    Dim oldScreenUpdating As Boolean, oldEnableEvents As Boolean
    Dim oldDisplayAlerts As Boolean, appStateSaved As Boolean
    Dim succeeded As Boolean

    On Error GoTo Fail

    Set wb = GetTargetWorkbook()
    Set wsK = FindWorksheet(wb, KHAI_SHEET_1, KHAI_SHEET_2)
    Set wsD = FindWorksheet(wb, DETAIL_SHEET_1, DETAIL_SHEET_2)
    Set wsS = FindWorksheet(wb, SUMMARY_SHEET_1, SUMMARY_SHEET_2)

    If wsK Is Nothing Then Err.Raise vbObjectError + 1600, , _
        "Khong tim thay sheet KHAI THUE."
    If wsD Is Nothing Then Err.Raise vbObjectError + 1601, , _
        "Khong tim thay sheet chi tiet hoa don/hoa don chi tiet."
    If wsS Is Nothing Then Err.Raise vbObjectError + 1602, , _
        "Khong tim thay sheet tong hop hoa don."

    DetectKhaiLayout wsK, False
    DetectDetailLayout wsD, False
    DetectSummaryLayout wsS, False
    If wsK.ProtectContents Then Err.Raise vbObjectError + 1603, , _
        "Sheet KHAI THUE dang duoc bao ve. Hay bo bao ve truoc khi chay."

    lastK = LastUsedRowInColumn(wsK, K_COL_NUMBER)
    lastD = LastUsedRowInColumn(wsD, D_COL_NUMBER)
    lastS = LastUsedRowInColumn(wsS, S_COL_NUMBER)
    If FIRST_KHAI_ROW <= 0 Or lastK < FIRST_KHAI_ROW Then _
        Err.Raise vbObjectError + 1604, , "Sheet KHAI THUE khong co du lieu."

    Set khaiGroups = NewTextDictionary()
    Set detailGroups = NewTextDictionary()
    Set summaryGroups = NewTextDictionary()
    Set stats = NewTextDictionary()

    If FIRST_DETAIL_ROW > 0 And lastD >= FIRST_DETAIL_ROW Then
        For r = FIRST_DETAIL_ROW To lastD
            If CanUseDetailFull(wsD, r) Then
                sourceKey = DetailFullKey(wsD, r)
                AddLongToGroup detailGroups, sourceKey, r
            End If
        Next r
    End If

    If lastS >= FIRST_SUMMARY_ROW Then
        For r = FIRST_SUMMARY_ROW To lastS
            If CanUseSummaryFull(wsS, r) Then
                sourceKey = SummaryFullKey(wsS, r)
                AddLongToGroup summaryGroups, sourceKey, r
            End If
        Next r
    End If

    For r = FIRST_KHAI_ROW To lastK
        If CanUseKhaiFull(wsK, r) Then
            sourceKey = KhaiFullKey(wsK, r)
            AddLongToGroup khaiGroups, sourceKey, r
        End If
    Next r

    oldCalculation = Application.Calculation
    oldScreenUpdating = Application.ScreenUpdating
    oldEnableEvents = Application.EnableEvents
    oldDisplayAlerts = Application.DisplayAlerts
    appStateSaved = True

    Application.ScreenUpdating = False
    Application.EnableEvents = False
    Application.DisplayAlerts = False
    Application.Calculation = xlCalculationManual
    Application.StatusBar = "Dang dien cac o trong I:J:K:L..."

    If CREATE_BACKUP_COPY Then backupPath = SaveBackupCopy(wb)
    cellsFilled = FillBlankIjklCertain( _
        wsK, wsD, wsS, khaiGroups, detailGroups, summaryGroups, _
        noResolvedKeys, noBlockedKeys, stats)
    succeeded = True

CleanExit:
    On Error Resume Next
    If appStateSaved Then
        Application.StatusBar = False
        Application.Calculation = oldCalculation
        Application.ScreenUpdating = oldScreenUpdating
        Application.EnableEvents = oldEnableEvents
        Application.DisplayAlerts = oldDisplayAlerts
    End If
    On Error GoTo 0

    If succeeded Then
        MsgBox "Da dien cac o trong I:J:K:L." & vbCrLf & vbCrLf & _
            "- Ten hang tu chi tiet/tong hop: " & _
                Format$(StatValue(stats, "ITEM_DETAIL"), "#,##0") & "/" & _
                Format$(StatValue(stats, "ITEM_SUMMARY"), "#,##0") & vbCrLf & _
            "- Don vi tu chi tiet/tong hop: " & _
                Format$(StatValue(stats, "UNIT_DETAIL"), "#,##0") & "/" & _
                Format$(StatValue(stats, "UNIT_SUMMARY"), "#,##0") & vbCrLf & _
            "- So luong tu chi tiet/tong hop: " & _
                Format$(StatValue(stats, "QTY_DETAIL"), "#,##0") & "/" & _
                Format$(StatValue(stats, "QTY_SUMMARY"), "#,##0") & vbCrLf & _
            "- Don gia tu chi tiet/tong hop: " & _
                Format$(StatValue(stats, "PRICE_DETAIL"), "#,##0") & "/" & _
                Format$(StatValue(stats, "PRICE_SUMMARY"), "#,##0") & vbCrLf & _
            "- Tong o da dien: " & Format$(cellsFilled, "#,##0") & vbCrLf & _
            "- Nhom/truong mo ho bo qua: " & _
                Format$(StatValue(stats, "AMBIGUOUS"), "#,##0") & vbCrLf & vbCrLf & _
            BackupMessage(backupPath) & _
            "Hay kiem tra va Save As thanh file .xlsm moi.", _
            vbInformation, "Cap nhat I:J:K:L"
    Else
        MsgBox errorText, vbCritical, "Khong the cap nhat I:J:K:L"
    End If
    Exit Sub

Fail:
    errorText = "Loi " & CStr(Err.Number) & ": " & Err.Description
    Resume CleanExit
End Sub

' Macro rieng, chi cap nhat cot Ten hang hoa/dich vu.
' Dung macro nay neu khong muon chen dong hay thay doi cac cot con lai.
Public Sub CapNhatTenHangHoaDichVu()
    Dim wb As Workbook
    Dim wsK As Worksheet, wsD As Worksheet, wsS As Worksheet
    Dim detailGroups As Object, summaryGroups As Object, khaiGroups As Object
    Dim altToDetailKeys As Object, coreToDetailKeys As Object
    Dim resolvedKeys As Object, detailOwners As Object
    Dim rowsForKey As Collection, detailRows As Collection
    Dim summaryRows As Collection, uniqueKeys As Collection
    Dim lastK As Long, lastD As Long, lastS As Long
    Dim r As Long, kKey As String, dKey As String
    Dim altKey As String, coreKey As String, candidateKey As String
    Dim key As Variant, summaryItem As Variant
    Dim exactMatches As Long, fallbackMatches As Long
    Dim detailUpdated As Long, summaryUpdated As Long
    Dim ambiguousOrMissing As Long
    Dim canExact As Boolean
    Dim backupPath As String
    Dim errorText As String
    Dim oldCalculation As XlCalculation
    Dim oldScreenUpdating As Boolean, oldEnableEvents As Boolean
    Dim oldDisplayAlerts As Boolean, appStateSaved As Boolean

    On Error GoTo Fail

    Set wb = GetTargetWorkbook()
    Set wsK = FindWorksheet(wb, KHAI_SHEET_1, KHAI_SHEET_2)
    Set wsD = FindWorksheet(wb, DETAIL_SHEET_1, DETAIL_SHEET_2)
    Set wsS = FindWorksheet(wb, SUMMARY_SHEET_1, SUMMARY_SHEET_2)

    If wsK Is Nothing Then Err.Raise vbObjectError + 1400, , _
        "Khong tim thay sheet KHAI THUE."
    If wsD Is Nothing Then Err.Raise vbObjectError + 1401, , _
        "Khong tim thay sheet chi tiet hoa don."
    If wsS Is Nothing Then Err.Raise vbObjectError + 1402, , _
        "Khong tim thay sheet tong hop hoa don."

    DetectKhaiLayout wsK
    DetectDetailLayout wsD
    DetectSummaryLayout wsS

    lastK = LastInvoiceRow(wsK)
    lastD = LastDetailRow(wsD)
    lastS = LastSummaryRow(wsS)
    If lastK < FIRST_KHAI_ROW Then Err.Raise vbObjectError + 1403, , _
        "Sheet KHAI THUE khong co du lieu."
    If lastD < FIRST_DETAIL_ROW Then Err.Raise vbObjectError + 1404, , _
        "Sheet chi tiet hoa don khong co du lieu."

    oldCalculation = Application.Calculation
    oldScreenUpdating = Application.ScreenUpdating
    oldEnableEvents = Application.EnableEvents
    oldDisplayAlerts = Application.DisplayAlerts
    appStateSaved = True

    Application.ScreenUpdating = False
    Application.EnableEvents = False
    Application.DisplayAlerts = False
    Application.Calculation = xlCalculationManual
    Application.StatusBar = "Dang doi chieu Ten hang hoa/dich vu..."

    Set detailGroups = NewTextDictionary()
    Set summaryGroups = NewTextDictionary()
    Set khaiGroups = NewTextDictionary()
    Set altToDetailKeys = NewTextDictionary()
    Set coreToDetailKeys = NewTextDictionary()
    Set resolvedKeys = NewTextDictionary()
    Set detailOwners = NewTextDictionary()

    For r = FIRST_DETAIL_ROW To lastD
        If Len(Trim$(SafeText(wsD.Cells(r, D_COL_NUMBER).Value2))) > 0 Then
            dKey = DetailFullKey(wsD, r)
            AddLongToGroup detailGroups, dKey, r

            If CanUseDetailFallback(wsD, r) Then
                altKey = DetailFallbackKey(wsD, r)
                AddUniqueTextToGroup altToDetailKeys, altKey, dKey
            End If
            If CanUseDetailCore(wsD, r) Then
                coreKey = DetailCoreKey(wsD, r)
                AddUniqueTextToGroup coreToDetailKeys, coreKey, dKey
            End If
        End If
    Next r

    If lastS >= FIRST_SUMMARY_ROW Then
        For r = FIRST_SUMMARY_ROW To lastS
            If CanUseSummaryFull(wsS, r) Then
                dKey = SummaryFullKey(wsS, r)
                AddLongToGroup summaryGroups, dKey, r
            End If
        Next r
    End If

    For r = FIRST_KHAI_ROW To lastK
        If Len(Trim$(SafeText(wsK.Cells(r, K_COL_NUMBER).Value2))) > 0 Then
            kKey = KhaiFullKey(wsK, r)
            AddLongToGroup khaiGroups, kKey, r
        End If
    Next r

    ' Resolve chi tiet: exact truoc, sau do fallback duy nhat va tong M/O khop.
    For Each key In khaiGroups.Keys
        kKey = CStr(key)
        Set rowsForKey = khaiGroups(kKey)
        canExact = False

        If detailGroups.Exists(kKey) Then
            Set detailRows = detailGroups(kKey)
            canExact = CanUseKhaiFull(wsK, CLng(rowsForKey(1))) And _
                       CanUseDetailFull(wsD, CLng(detailRows(1)))
        End If

        If canExact Then
            If Not detailOwners.Exists(kKey) Then
                resolvedKeys.Add kKey, kKey
                detailOwners.Add kKey, kKey
                exactMatches = exactMatches + 1
            End If
        Else
            If CanUseKhaiCore(wsK, CLng(rowsForKey(1))) Then
                coreKey = KhaiCoreKey(wsK, CLng(rowsForKey(1)))
                If coreToDetailKeys.Exists(coreKey) Then
                    Set uniqueKeys = coreToDetailKeys(coreKey)
                    If uniqueKeys.Count = 1 Then
                        candidateKey = CStr(uniqueKeys(1))
                        Set detailRows = detailGroups(candidateKey)
                        If DatesCompatibleForFallback( _
                                wsK, CLng(rowsForKey(1)), _
                                wsD, CLng(detailRows(1))) And _
                           GroupsReconcile(wsK, rowsForKey, wsD, detailRows) Then
                            If Not detailOwners.Exists(candidateKey) Then
                                resolvedKeys.Add kKey, candidateKey
                                detailOwners.Add candidateKey, kKey
                                fallbackMatches = fallbackMatches + 1
                            End If
                        End If
                    End If
                End If
            End If

            If Not resolvedKeys.Exists(kKey) And _
               ALLOW_UNIQUE_SERIES_FALLBACK Then
                If CanUseKhaiFallback(wsK, CLng(rowsForKey(1))) Then
                    altKey = KhaiFallbackKey(wsK, CLng(rowsForKey(1)))
                    If altToDetailKeys.Exists(altKey) Then
                        Set uniqueKeys = altToDetailKeys(altKey)
                        If uniqueKeys.Count = 1 Then
                            candidateKey = CStr(uniqueKeys(1))
                            Set detailRows = detailGroups(candidateKey)
                            If GroupsReconcile(wsK, rowsForKey, wsD, detailRows) Then
                                If Not detailOwners.Exists(candidateKey) Then
                                    resolvedKeys.Add kKey, candidateKey
                                    detailOwners.Add candidateKey, kKey
                                    fallbackMatches = fallbackMatches + 1
                                End If
                            End If
                        End If
                    End If
                End If
            End If
        End If
    Next key

    ' Backup phai co truoc khi sua bat ky o nao.
    If CREATE_BACKUP_COPY Then backupPath = SaveBackupCopy(wb)

    ' Uu tien 1: chi tiet. Neu nhieu dong W giong nhau, coi la mot gia tri duy nhat.
    ' Neu W khac nhau, chi gan khi mot dong chi tiet khop duy nhat voi dong KHAI THUE.
    For Each key In resolvedKeys.Keys
        kKey = CStr(key)
        dKey = CStr(resolvedKeys(kKey))
        Set rowsForKey = khaiGroups(kKey)
        Set detailRows = detailGroups(dKey)
        summaryItem = Empty
        If summaryGroups.Exists(dKey) Then
            TryUniqueSummaryValue wsS, summaryGroups(dKey), S_COL_ITEM, summaryItem
        End If
        detailUpdated = detailUpdated + ApplyDescriptionPriorityForGroup( _
            wsK, rowsForKey, wsD, detailRows, summaryItem)
    Next key

    ' Uu tien 2: chi dung tong hop exact khi khong resolve duoc chi tiet.
    For Each key In khaiGroups.Keys
        kKey = CStr(key)
        If Not resolvedKeys.Exists(kKey) Then
            Set rowsForKey = khaiGroups(kKey)
            If rowsForKey.Count = 1 And _
               CanUseKhaiFull(wsK, CLng(rowsForKey(1))) Then
                If summaryGroups.Exists(kKey) Then
                    Set summaryRows = summaryGroups(kKey)
                    If summaryRows.Count = 1 And _
                       CanUseSummaryFull(wsS, CLng(summaryRows(1))) Then
                        summaryItem = Empty
                        If TryUniqueSummaryValue( _
                                wsS, summaryRows, S_COL_ITEM, summaryItem) Then
                            summaryUpdated = summaryUpdated + _
                                ApplySummaryDescriptionForGroup( _
                                    wsK, rowsForKey, summaryItem)
                        End If
                    End If
                End If
            End If
        End If
    Next key

    ambiguousOrMissing = khaiGroups.Count - resolvedKeys.Count

CleanExit:
    On Error Resume Next
    If appStateSaved Then
        Application.Calculation = oldCalculation
        Application.ScreenUpdating = oldScreenUpdating
        Application.EnableEvents = oldEnableEvents
        Application.DisplayAlerts = oldDisplayAlerts
    End If
    Application.StatusBar = False
    On Error GoTo 0

    MsgBox "Da cap nhat Ten hang hoa/dich vu." & vbCrLf & vbCrLf & _
        "- Khop exact: " & Format$(exactMatches, "#,##0") & vbCrLf & _
        "- Khop fallback an toan: " & Format$(fallbackMatches, "#,##0") & vbCrLf & _
        "- O thay theo chi tiet W: " & Format$(detailUpdated, "#,##0") & vbCrLf & _
        "- O thay theo tong hop W: " & Format$(summaryUpdated, "#,##0") & vbCrLf & _
        "- Nhom khong resolve chi tiet: " & _
            Format$(ambiguousOrMissing, "#,##0") & vbCrLf & vbCrLf & _
        BackupMessage(backupPath) & _
        "Hay Save As de luu thanh mot file .xlsm moi.", _
        vbInformation, "Cap nhat Ten hang hoa/dich vu"
    Exit Sub

Fail:
    errorText = "Loi " & CStr(Err.Number) & ": " & Err.Description
    On Error Resume Next
    If appStateSaved Then
        Application.Calculation = oldCalculation
        Application.ScreenUpdating = oldScreenUpdating
        Application.EnableEvents = oldEnableEvents
        Application.DisplayAlerts = oldDisplayAlerts
    End If
    Application.StatusBar = False
    On Error GoTo 0
    MsgBox errorText, vbCritical, "Khong the cap nhat Ten hang hoa/dich vu"
End Sub

Public Sub CapNhatKhaiThueTuChiTiet()
    Dim wb As Workbook
    Dim wsK As Worksheet, wsD As Worksheet, wsS As Worksheet
    Dim detailGroups As Object, khaiGroups As Object, summaryGroups As Object
    Dim altToDetailKeys As Object, coreToDetailKeys As Object, resolvedKeys As Object
    Dim assignments As Object, singleMap As Object, detailOwners As Object, blockedKeys As Object
    Dim rowSummaryItems As Object, ijklStats As Object
    Dim lastK As Long, lastD As Long, lastS As Long
    Dim r As Long, i As Long, dKey As String, kKey As String, altKey As String, coreKey As String
    Dim key As Variant, candidateKey As String, summaryItem As Variant
    Dim rowsForKey As Collection, detailRows As Collection, summaryRows As Collection
    Dim uniqueKeys As Collection
    Dim issueRows As Collection
    Dim canExact As Boolean
    Dim expandedGroups As Long, addedRows As Long
    Dim singleRowsFilled As Long, cellsFilled As Long
    Dim filledNow As Long
    Dim matchedLines As Long
    Dim exactMatches As Long, fallbackMatches As Long
    Dim skippedAlreadyExpanded As Long, skippedAmbiguous As Long
    Dim unmatchedGroups As Long
    Dim quantityRounded As Long, priceRounded As Long
    Dim quantityIssues As Long, priceIssues As Long
    Dim formulaErrors As Long, placeholderCount As Long
    Dim totalFormulaIssues As Long
    Dim dataRows As Long
    Dim totalAmount As Double, totalVat As Double
    Dim totalAmountBefore As Double, totalVatBefore As Double
    Dim amountCellsFilled As Long, vatCellsFilled As Long
    Dim amountWasBlank As Boolean, vatWasBlank As Boolean
    Dim expectedAmountIncrease As Double, expectedVatIncrease As Double
    Dim oldCalculation As XlCalculation
    Dim oldScreenUpdating As Boolean, oldEnableEvents As Boolean
    Dim oldDisplayAlerts As Boolean
    Dim succeeded As Boolean, errorText As String
    Dim summaryText As String
    Dim runStatus As String
    Dim runId As String
    Dim backupPath As String
    Dim appStateSaved As Boolean

    If MsgBox( _
        "Macro se thuc hien toan bo quy trinh cho sheet KHAI THUE:" & vbCrLf & _
        "- Doi chieu va bo sung cac dong chi tiet hoa don." & vbCrLf & _
        "- Dien o trong Ten hang/Don vi/So luong/Don gia tu hai sheet nguon." & vbCrLf & _
        "- Dien o trong khi co doi chieu duy nhat, chac chan." & vbCrLf & _
        "- Lam tron So luong (2 so le) va Don gia (so nguyen)." & vbCrLf & _
        "- Kiem tra va ghi ket qua vao sheet NHAT_KY_VBA." & vbCrLf & _
        "Macro se tao mot ban sao truoc khi sua (neu workbook da duoc luu)." & vbCrLf & vbCrLf & _
        "Tiep tuc?", _
        vbQuestion + vbYesNo, _
        "Cap nhat chi tiet hoa don") <> vbYes Then Exit Sub

    On Error GoTo Fail

    Set wb = GetTargetWorkbook()
    runId = Format$(Now, "yyyymmdd_hhnnss")
    Set wsK = FindWorksheet(wb, KHAI_SHEET_1, KHAI_SHEET_2)
    Set wsD = FindWorksheet(wb, DETAIL_SHEET_1, DETAIL_SHEET_2)
    Set wsS = FindWorksheet(wb, SUMMARY_SHEET_1, SUMMARY_SHEET_2)

    If wsK Is Nothing Then Err.Raise vbObjectError + 1000, , "Khong tim thay sheet 'KHAI THUE'."
    If wsD Is Nothing Then Err.Raise vbObjectError + 1001, , "Khong tim thay sheet 'chi tiet hoa don'."
    If wsS Is Nothing Then Err.Raise vbObjectError + 1005, , "Khong tim thay sheet 'tong hop hoa don'."
    DetectAllLayouts wsK, wsD, wsS
    If wsK.ProtectContents Then Err.Raise vbObjectError + 1002, , "Sheet KHAI THUE dang duoc bao ve. Hay bo bao ve truoc khi chay."

    oldCalculation = Application.Calculation
    oldScreenUpdating = Application.ScreenUpdating
    oldEnableEvents = Application.EnableEvents
    oldDisplayAlerts = Application.DisplayAlerts
    appStateSaved = True

    Application.ScreenUpdating = False
    Application.EnableEvents = False
    Application.DisplayAlerts = False
    Application.Calculation = xlCalculationManual
    Application.StatusBar = "Dang lap chi muc hoa don..."

    lastK = LastInvoiceRow(wsK)
    lastD = LastDetailRow(wsD)
    lastS = LastSummaryRow(wsS)
    If lastK < FIRST_KHAI_ROW Then Err.Raise vbObjectError + 1003, , "Sheet KHAI THUE khong co dong du lieu."
    If lastD < FIRST_DETAIL_ROW Then Err.Raise vbObjectError + 1004, , "Sheet chi tiet hoa don khong co dong du lieu."
    If lastS < FIRST_SUMMARY_ROW Then Err.Raise vbObjectError + 1006, , "Sheet tong hop hoa don khong co dong du lieu."

    totalAmountBefore = SumInvoiceColumn(wsK, lastK, K_COL_AMOUNT)
    totalVatBefore = SumInvoiceColumn(wsK, lastK, K_COL_VAT)

    Set detailGroups = NewTextDictionary()
    Set khaiGroups = NewTextDictionary()
    Set summaryGroups = NewTextDictionary()
    Set altToDetailKeys = NewTextDictionary()
    Set coreToDetailKeys = NewTextDictionary()
    Set resolvedKeys = NewTextDictionary()
    Set assignments = NewTextDictionary()
    Set singleMap = NewTextDictionary()
    Set detailOwners = NewTextDictionary()
    Set blockedKeys = NewTextDictionary()
    Set rowSummaryItems = NewTextDictionary()
    Set ijklStats = NewTextDictionary()
    Set issueRows = New Collection

    ' 1) Lap nhom du lieu chi tiet.
    For r = FIRST_DETAIL_ROW To lastD
        If Len(Trim$(SafeText(wsD.Cells(r, D_COL_NUMBER).Value2))) > 0 Then
            dKey = DetailFullKey(wsD, r)
            AddLongToGroup detailGroups, dKey, r

            If CanUseDetailFallback(wsD, r) Then
                altKey = DetailFallbackKey(wsD, r)
                AddUniqueTextToGroup altToDetailKeys, altKey, dKey
            End If
            If CanUseDetailCore(wsD, r) Then
                coreKey = DetailCoreKey(wsD, r)
                AddUniqueTextToGroup coreToDetailKeys, coreKey, dKey
            End If
        End If
    Next r

    ' 1b) Lap nhom tong hop de xac nhan khoa va tong tien nguon.
    ' Dong tong cuoi bang khong co so hoa don nen tu dong bi bo qua.
    For r = FIRST_SUMMARY_ROW To lastS
        If CanUseSummaryFull(wsS, r) Then
            dKey = SummaryFullKey(wsS, r)
            AddLongToGroup summaryGroups, dKey, r
        End If
    Next r

    ' 2) Lap nhom du lieu tren KHAI THUE theo vi tri goc.
    For r = FIRST_KHAI_ROW To lastK
        If Len(Trim$(SafeText(wsK.Cells(r, K_COL_NUMBER).Value2))) > 0 Then
            kKey = KhaiFullKey(wsK, r)
            AddLongToGroup khaiGroups, kKey, r
        End If
    Next r

    ' 3) Giai khoa doi chieu: uu tien khoa day du; fallback chi khi duy nhat va tong khop.
    For Each key In khaiGroups.Keys
        kKey = CStr(key)
        Set rowsForKey = khaiGroups(kKey)
        canExact = False
        If detailGroups.Exists(kKey) Then
            Set detailRows = detailGroups(kKey)
            canExact = CanUseKhaiFull(wsK, CLng(rowsForKey(1))) And _
                       CanUseDetailFull(wsD, CLng(detailRows(1)))
        End If

        If canExact Then
            If Not detailOwners.Exists(kKey) Then
                resolvedKeys.Add kKey, kKey
                detailOwners.Add kKey, kKey
                exactMatches = exactMatches + 1
            Else
                skippedAmbiguous = skippedAmbiguous + 1
            End If
        Else
            ' Fallback 1: cung Mau + Ky hieu + So + MST, cho phep ngay sai/trong,
            ' nhung chi nhan khi chi co mot nhom chi tiet va tong M/O khop.
            If CanUseKhaiCore(wsK, CLng(rowsForKey(1))) Then
                coreKey = KhaiCoreKey(wsK, CLng(rowsForKey(1)))
                If coreToDetailKeys.Exists(coreKey) Then
                    Set uniqueKeys = coreToDetailKeys(coreKey)
                    If uniqueKeys.Count = 1 Then
                        candidateKey = CStr(uniqueKeys(1))
                        Set detailRows = detailGroups(candidateKey)
                        If DatesCompatibleForFallback( _
                                wsK, CLng(rowsForKey(1)), _
                                wsD, CLng(detailRows(1))) And _
                           GroupsReconcile(wsK, rowsForKey, wsD, detailRows) Then
                            If Not khaiGroups.Exists(candidateKey) And _
                               Not detailOwners.Exists(candidateKey) Then
                                resolvedKeys.Add kKey, candidateKey
                                detailOwners.Add candidateKey, kKey
                                fallbackMatches = fallbackMatches + 1
                            Else
                                skippedAmbiguous = skippedAmbiguous + 1
                            End If
                        End If
                    End If
                End If
            End If

            ' Fallback 2: bo qua Ky hieu, bat buoc Mau + So + Ngay + MST
            ' va van phai la ung vien duy nhat, tong M/O khop.
            If Not resolvedKeys.Exists(kKey) And ALLOW_UNIQUE_SERIES_FALLBACK Then
                If CanUseKhaiFallback(wsK, CLng(rowsForKey(1))) Then
                    altKey = KhaiFallbackKey(wsK, CLng(rowsForKey(1)))

                    If altToDetailKeys.Exists(altKey) Then
                        Set uniqueKeys = altToDetailKeys(altKey)
                        If uniqueKeys.Count = 1 Then
                            candidateKey = CStr(uniqueKeys(1))
                            Set detailRows = detailGroups(candidateKey)

                            If GroupsReconcile(wsK, rowsForKey, wsD, detailRows) Then
                                If Not khaiGroups.Exists(candidateKey) And _
                                   Not detailOwners.Exists(candidateKey) Then
                                    resolvedKeys.Add kKey, candidateKey
                                    detailOwners.Add candidateKey, kKey
                                    fallbackMatches = fallbackMatches + 1
                                Else
                                    skippedAmbiguous = skippedAmbiguous + 1
                                End If
                            End If
                        End If
                    End If
                End If
            End If
        End If
    Next key

    unmatchedGroups = khaiGroups.Count - resolvedKeys.Count
    For Each key In khaiGroups.Keys
        kKey = CStr(key)
        If Not resolvedKeys.Exists(kKey) Then
            Set rowsForKey = khaiGroups(kKey)
            AddIssueEntry issueRows, kKey, vbNullString, "BO_QUA", _
                "KHONG_CO_UNG_VIEN_DUY_NHAT_HOAC_TONG_KHONG_KHOP", _
                rowsForKey.Count, 0
        End If
    Next key

    ' 4) Chuan bi ke hoach truoc khi chen dong.
    Application.StatusBar = "Dang lap ke hoach chen dong..."
    For Each key In resolvedKeys.Keys
        kKey = CStr(key)
        dKey = CStr(resolvedKeys(kKey))
        Set rowsForKey = khaiGroups(kKey)
        Set detailRows = detailGroups(dKey)

        ' Exact 1-1 theo Ky hieu + So + Ngay + MST la doi chieu du manh;
        ' khong bat buoc tong hop phai co du lieu de dien cac o con trong.
        If detailRows.Count = 1 And rowsForKey.Count = 1 And _
           StrComp(kKey, dKey, vbTextCompare) = 0 Then
            singleMap.Add CStr(CLng(rowsForKey(1))), CLng(detailRows(1))
        ElseIf Not DetailGroupConfirmedBySummary( _
                wsD, detailRows, wsS, summaryGroups, dKey) Then
            MarkBlockedKey blockedKeys, kKey
            skippedAmbiguous = skippedAmbiguous + 1
            AddIssueEntry issueRows, kKey, dKey, "BO_QUA", _
                "CHI_TIET_VA_TONG_HOP_KHONG_KHOP_HOAC_KHONG_DUY_NHAT", _
                rowsForKey.Count, detailRows.Count
        ElseIf detailRows.Count = 1 And rowsForKey.Count = 1 Then
            If AssignmentReconciles(wsK, CLng(rowsForKey(1)), wsD, detailRows) Then
                singleMap.Add CStr(CLng(rowsForKey(1))), CLng(detailRows(1))
            Else
                MarkBlockedKey blockedKeys, kKey
                skippedAmbiguous = skippedAmbiguous + 1
                AddIssueEntry issueRows, kKey, dKey, "BO_QUA", _
                    "M_O_KHONG_KHOP_CHO_NHOM_1_1", rowsForKey.Count, detailRows.Count
            End If
        Else
            matchedLines = CountMaterializedLines(wsK, rowsForKey, wsD, detailRows)

            If rowsForKey.Count = detailRows.Count Then
                ' Du/du dong: chi danh dau hoan tat neu noi dung cung khop.
                ' Neu noi dung khong khop thi bo qua an toan, khong chen them.
                If matchedLines = detailRows.Count And _
                   GroupsReconcile(wsK, rowsForKey, wsD, detailRows) Then
                    skippedAlreadyExpanded = skippedAlreadyExpanded + 1
                Else
                    MarkBlockedKey blockedKeys, kKey
                    skippedAmbiguous = skippedAmbiguous + 1
                    AddIssueEntry issueRows, kKey, dKey, "BO_QUA", _
                        "SO_DONG_BANG_NHAU_NHUNG_NOI_DUNG_HOAC_TONG_KHONG_KHOP", _
                        rowsForKey.Count, detailRows.Count
                End If
            ElseIf rowsForKey.Count > detailRows.Count Then
                ' Co dong du/duplicate: luon coi la mo ho, khong tu dong bo qua.
                MarkBlockedKey blockedKeys, kKey
                skippedAmbiguous = skippedAmbiguous + 1
                AddIssueEntry issueRows, kKey, dKey, "BO_QUA", _
                    "KHAI_THUE_CO_DONG_DU_HOAC_TRUNG", rowsForKey.Count, detailRows.Count
            ElseIf matchedLines > 0 Then
                ' Nhom da bung do: khong append lai toan bo vi se tao dong trung.
                MarkBlockedKey blockedKeys, kKey
                skippedAmbiguous = skippedAmbiguous + 1
                AddIssueEntry issueRows, kKey, dKey, "BO_QUA", _
                    "NHOM_DA_BUNG_DO_KHONG_APPEND_LAI", rowsForKey.Count, detailRows.Count
            ElseIf detailRows.Count > rowsForKey.Count Then
                If BuildAssignmentsForGroup( _
                        wsK, rowsForKey, wsD, detailRows, assignments) Then
                    expandedGroups = expandedGroups + 1
                Else
                    MarkBlockedKey blockedKeys, kKey
                    skippedAmbiguous = skippedAmbiguous + 1
                    AddIssueEntry issueRows, kKey, dKey, "BO_QUA", _
                        "KHONG_CHIA_DUOC_CHI_TIET_THEO_THUE_SUAT_VA_TONG", _
                        rowsForKey.Count, detailRows.Count
                End If
            Else
                MarkBlockedKey blockedKeys, kKey
                skippedAmbiguous = skippedAmbiguous + 1
                AddIssueEntry issueRows, kKey, dKey, "BO_QUA", _
                    "TRANG_THAI_NHOM_KHONG_XAC_DINH", rowsForKey.Count, detailRows.Count
            End If
        End If
    Next key

    ' Tao ban sao .xlsm truoc BAT KY thay doi nao de co the khoi phuc neu can.
    If CREATE_BACKUP_COPY Then backupPath = SaveBackupCopy(wb)

    ' Dien rieng cac o trong I:J:K:L cho moi nhom da doi chieu an toan.
    ' Neu khong co nhom chi tiet resolve, van cho phep dung tong hop exact.
    cellsFilled = cellsFilled + FillBlankIjklCertain( _
        wsK, wsD, wsS, khaiGroups, detailGroups, summaryGroups, _
        resolvedKeys, blockedKeys, ijklStats)

    ' Uu tien mo ta cho moi nhom da doi chieu chac chan:
    ' 1) Dien giai chi tiet; 2) Dien giai tong hop; 3) noi dung KHAI THUE goc.
    ' Quy tac nay duoc phep thay noi dung goc cua rieng cot Ten hang hoa/dich vu.
    ' Khong bat buoc phai co tong hop neu chi tiet da khop khoa hoa don exact/fallback
    ' an toan. Tong hop chi la nguon mo ta uu tien 2, khong phai dieu kien chan.
    For Each key In resolvedKeys.Keys
        kKey = CStr(key)
        If Not blockedKeys.Exists(kKey) Then
            dKey = CStr(resolvedKeys(kKey))
            Set rowsForKey = khaiGroups(kKey)
            Set detailRows = detailGroups(dKey)
            summaryItem = Empty
            If summaryGroups.Exists(dKey) Then
                TryUniqueSummaryValue wsS, summaryGroups(dKey), S_COL_ITEM, summaryItem
            End If
            ApplyDescriptionPriorityForGroup _
                wsK, rowsForKey, wsD, detailRows, summaryItem

            ' Chi luu mo ta tong hop cho buoc bung dong neu nhom du dieu kien sua I:O.
            For i = 1 To rowsForKey.Count
                If Not IsSourceMissing(summaryItem) Then
                    rowSummaryItems(CStr(CLng(rowsForKey(i)))) = summaryItem
                End If
            Next i
        End If
    Next key

    ' Neu khong co nhom chi tiet duoc resolve, moi thu nguon uu tien 2.
    ' Chi dung khoa day du exact va Dien giai tong hop duy nhat de tranh gan nham.
    For Each key In khaiGroups.Keys
        kKey = CStr(key)
        If Not resolvedKeys.Exists(kKey) Then
            Set rowsForKey = khaiGroups(kKey)
            If rowsForKey.Count = 1 And _
               CanUseKhaiFull(wsK, CLng(rowsForKey(1))) Then
                If summaryGroups.Exists(kKey) Then
                    Set summaryRows = summaryGroups(kKey)
                    If summaryRows.Count = 1 And _
                       CanUseSummaryFull(wsS, CLng(summaryRows(1))) Then
                        summaryItem = Empty
                        If TryUniqueSummaryValue( _
                                wsS, summaryRows, S_COL_ITEM, summaryItem) Then
                            ApplySummaryDescriptionForGroup _
                                wsK, rowsForKey, summaryItem
                        End If
                    End If
                End If
            End If
        End If
    Next key

    If issueRows.Count > 0 Then AppendIssueLog wb, runId, issueRows

    ' Dien C:H chi sau khi hoa don da duoc resolve duy nhat va cac dong nguon
    ' thong nhat. Khong suy dien tu mot ung vien mo ho.
    For Each key In resolvedKeys.Keys
        kKey = CStr(key)
        If Not blockedKeys.Exists(kKey) Then
            dKey = CStr(resolvedKeys(kKey))
            Set rowsForKey = khaiGroups(kKey)
            Set detailRows = detailGroups(dKey)
            cellsFilled = cellsFilled + FillHeaderBlanksCertain(wsK, rowsForKey, wsD, detailRows)
        End If
    Next key

    ' 5) Chen tu duoi len de cac so dong goc chua xu ly khong bi thay doi.
    Application.StatusBar = "Dang cap nhat sheet KHAI THUE..."
    For r = lastK To FIRST_KHAI_ROW Step -1
        If assignments.Exists(CStr(r)) Then
            Set detailRows = assignments(CStr(r))
            summaryItem = Empty
            If rowSummaryItems.Exists(CStr(r)) Then summaryItem = rowSummaryItems(CStr(r))
            ExpandOneKhaiRow wsK, wsD, r, detailRows, summaryItem
            addedRows = addedRows + detailRows.Count - 1
        ElseIf singleMap.Exists(CStr(r)) Then
            amountWasBlank = IsBlankValue(wsK.Cells(r, K_COL_AMOUNT).Value2)
            vatWasBlank = IsBlankValue(wsK.Cells(r, K_COL_VAT).Value2)
            summaryItem = Empty
            If rowSummaryItems.Exists(CStr(r)) Then summaryItem = rowSummaryItems(CStr(r))
            filledNow = FillSingleDetailBlanks( _
                wsK, wsD, r, CLng(singleMap(CStr(r))), summaryItem)
            If amountWasBlank And Not IsBlankValue(wsK.Cells(r, K_COL_AMOUNT).Value2) Then
                amountCellsFilled = amountCellsFilled + 1
                expectedAmountIncrease = expectedAmountIncrease + _
                    SafeDouble(wsK.Cells(r, K_COL_AMOUNT).Value2)
            End If
            If vatWasBlank And Not IsBlankValue(wsK.Cells(r, K_COL_VAT).Value2) Then
                vatCellsFilled = vatCellsFilled + 1
                expectedVatIncrease = expectedVatIncrease + _
                    SafeDouble(wsK.Cells(r, K_COL_VAT).Value2)
            End If
            If filledNow > 0 Then
                singleRowsFilled = singleRowsFilled + 1
                cellsFilled = cellsFilled + filledNow
            End If
        End If
    Next r

    ' 6) Lam tron truc tiep K/L. Khong tinh lai M/O tu K*L vi so tien hoa don
    ' phai duoc giu nguyen sau khi lam tron so luong va don gia.
    Application.StatusBar = "Dang lam tron so luong va don gia..."
    lastK = LastInvoiceRow(wsK)
    RoundQuantityAndPrice wsK, lastK, quantityRounded, priceRounded
    ExtendTaxRateValidation wsK, lastK
    UpdateSimpleTotalFormula wsK, lastK, K_COL_AMOUNT
    UpdateSimpleTotalFormula wsK, lastK, K_COL_VAT

    Application.StatusBar = "Dang tinh lai cong thuc..."
    wsK.Calculate

    ' 7) Hau kiem toan bang va ghi nhat ky de dung cho cac thang sau.
    Application.StatusBar = "Dang kiem tra va ghi nhat ky..."
    AuditWorkbook wb, wsK, lastK, dataRows, quantityIssues, priceIssues, _
        formulaErrors, placeholderCount, totalAmount, totalVat
    totalFormulaIssues = CountTotalFormulaMismatches(wsK, lastK, totalAmount, totalVat)
    If totalFormulaIssues > 0 Then
        Err.Raise vbObjectError + 1052, , _
            "Cong thuc tong M/O ben duoi bang chua bao gom dung tat ca dong hoa don."
    End If
    If Abs((totalAmount - totalAmountBefore) - expectedAmountIncrease) > 0.5 Then
        Err.Raise vbObjectError + 1050, , _
            "Tong truoc thue M thay doi ngoai du kien: " & _
            Format$(totalAmountBefore, "#,##0") & " -> " & Format$(totalAmount, "#,##0")
    End If
    If Abs((totalVat - totalVatBefore) - expectedVatIncrease) > 0.5 Then
        Err.Raise vbObjectError + 1051, , _
            "Tong thue O thay doi ngoai du kien: " & _
            Format$(totalVatBefore, "#,##0") & " -> " & Format$(totalVat, "#,##0")
    End If
    UpdateStaticDuplicateTotals wsK, lastK, K_COL_AMOUNT, totalAmountBefore, totalAmount
    UpdateStaticDuplicateTotals wsK, lastK, K_COL_VAT, totalVatBefore, totalVat
    runStatus = "THANH_CONG"
    If quantityIssues > 0 Or priceIssues > 0 Or formulaErrors > 0 Or _
       placeholderCount > 0 Or skippedAmbiguous > 0 Or unmatchedGroups > 0 Then
        runStatus = "HOAN_TAT_CO_CANH_BAO"
    End If
    AppendRunLog wb, "CHAY_TAT_CA|" & runId & _
        "|IJKL_MO_HO=" & CStr(StatValue(ijklStats, "AMBIGUOUS")), _
        runStatus, dataRows, exactMatches, _
        fallbackMatches, expandedGroups, addedRows, singleRowsFilled, cellsFilled, _
        quantityRounded, priceRounded, unmatchedGroups, skippedAmbiguous, _
        quantityIssues, priceIssues, formulaErrors, placeholderCount, _
        totalAmount, totalVat, backupPath
    succeeded = True

CleanExit:
    On Error Resume Next
    If appStateSaved Then
        Application.CutCopyMode = False
        Application.StatusBar = False
        Application.Calculation = oldCalculation
        Application.ScreenUpdating = oldScreenUpdating
        Application.EnableEvents = oldEnableEvents
        Application.DisplayAlerts = oldDisplayAlerts
    End If
    On Error GoTo 0

    If succeeded Then
        summaryText = "Da hoan tat cap nhat." & vbCrLf & vbCrLf
        summaryText = summaryText & "- Khop khoa day du: " & Format$(exactMatches, "#,##0") & " hoa don" & vbCrLf
        summaryText = summaryText & "- Khop fallback duy nhat (ngay/ky hieu): " & Format$(fallbackMatches, "#,##0") & " hoa don" & vbCrLf
        summaryText = summaryText & "- Hoa don duoc bung chi tiet: " & Format$(expandedGroups, "#,##0") & vbCrLf
        summaryText = summaryText & "- Dong chi tiet da them: " & Format$(addedRows, "#,##0") & vbCrLf
        summaryText = summaryText & "- Dong mot chi tiet duoc dien o trong: " & Format$(singleRowsFilled, "#,##0") & vbCrLf
        summaryText = summaryText & "- Tong o trong da dien: " & Format$(cellsFilled, "#,##0") & vbCrLf
        summaryText = summaryText & "- Ten hang tu chi tiet/tong hop: " & _
            Format$(StatValue(ijklStats, "ITEM_DETAIL"), "#,##0") & "/" & _
            Format$(StatValue(ijklStats, "ITEM_SUMMARY"), "#,##0") & vbCrLf
        summaryText = summaryText & "- Don vi tu chi tiet/tong hop: " & _
            Format$(StatValue(ijklStats, "UNIT_DETAIL"), "#,##0") & "/" & _
            Format$(StatValue(ijklStats, "UNIT_SUMMARY"), "#,##0") & vbCrLf
        summaryText = summaryText & "- So luong tu chi tiet/tong hop: " & _
            Format$(StatValue(ijklStats, "QTY_DETAIL"), "#,##0") & "/" & _
            Format$(StatValue(ijklStats, "QTY_SUMMARY"), "#,##0") & vbCrLf
        summaryText = summaryText & "- Don gia tu chi tiet/tong hop: " & _
            Format$(StatValue(ijklStats, "PRICE_DETAIL"), "#,##0") & "/" & _
            Format$(StatValue(ijklStats, "PRICE_SUMMARY"), "#,##0") & vbCrLf
        summaryText = summaryText & "- O So luong da lam tron: " & Format$(quantityRounded, "#,##0") & vbCrLf
        summaryText = summaryText & "- O Don gia da lam tron: " & Format$(priceRounded, "#,##0") & vbCrLf
        summaryText = summaryText & "- Nhom da du dong va duoc giu nguyen: " & Format$(skippedAlreadyExpanded, "#,##0") & vbCrLf
        summaryText = summaryText & "- Nhom mo ho duoc bo qua: " & Format$(skippedAmbiguous, "#,##0") & vbCrLf
        summaryText = summaryText & "- Nhom khong co doi chieu chac chan: " & Format$(unmatchedGroups, "#,##0") & vbCrLf & vbCrLf
        summaryText = summaryText & "Hau kiem:" & vbCrLf
        summaryText = summaryText & "- Dong du lieu: " & Format$(dataRows, "#,##0") & vbCrLf
        summaryText = summaryText & "- Loi do chinh xac K/L: " & Format$(quantityIssues, "#,##0") & "/" & Format$(priceIssues, "#,##0") & vbCrLf
        summaryText = summaryText & "- Loi cong thuc: " & Format$(formulaErrors, "#,##0") & vbCrLf
        summaryText = summaryText & "- Placeholder 1916 trong I:O: " & Format$(placeholderCount, "#,##0") & vbCrLf
        summaryText = summaryText & "- Tong truoc thue: " & Format$(totalAmount, "#,##0") & vbCrLf
        summaryText = summaryText & "- Tong thue: " & Format$(totalVat, "#,##0") & vbCrLf & vbCrLf
        summaryText = summaryText & BackupMessage(backupPath)
        summaryText = summaryText & "Chi tiet lan chay da ghi vao sheet '" & LOG_SHEET_NAME & "'." & vbCrLf
        summaryText = summaryText & "Macro chua tu luu file. Hay kiem tra roi dung Save As de luu mot ban .xlsm moi."
        MsgBox summaryText, vbInformation, "Cap nhat hoan tat"
    Else
        If Len(backupPath) > 0 Then
            errorText = errorText & vbCrLf & vbCrLf & _
                "Workbook co the da duoc sua do dang. Ban sao truoc khi sua: " & backupPath
        End If
        MsgBox errorText, vbCritical, "Khong the cap nhat"
    End If
    Exit Sub

Fail:
    errorText = "Loi " & CStr(Err.Number) & ": " & Err.Description
    Resume CleanExit
End Sub

' Chi lam tron K/L, khong doi chieu hay chen dong.
Public Sub LamTronSoLuongVaDonGia()
    Dim wb As Workbook, wsK As Worksheet
    Dim lastK As Long, quantityRounded As Long, priceRounded As Long
    Dim quantityIssues As Long, priceIssues As Long
    Dim formulaErrors As Long, placeholderCount As Long, dataRows As Long
    Dim totalAmount As Double, totalVat As Double
    Dim totalAmountBefore As Double, totalVatBefore As Double
    Dim backupPath As String, errorText As String
    Dim runStatus As String
    Dim oldCalculation As XlCalculation
    Dim oldScreenUpdating As Boolean, oldEnableEvents As Boolean
    Dim appStateSaved As Boolean, succeeded As Boolean

    If MsgBox( _
        "Chi lam tron cot So luong K ve 2 so le va cot Don gia L ve so nguyen?" & vbCrLf & _
        "Cot Thanh tien M va Tien thue O se duoc giu nguyen.", _
        vbQuestion + vbYesNo, "Lam tron K/L") <> vbYes Then Exit Sub

    On Error GoTo Fail
    Set wb = GetTargetWorkbook()
    Set wsK = FindWorksheet(wb, KHAI_SHEET_1, KHAI_SHEET_2)
    If wsK Is Nothing Then Err.Raise vbObjectError + 1100, , "Khong tim thay sheet KHAI THUE."
    DetectKhaiLayout wsK
    If wsK.ProtectContents Then Err.Raise vbObjectError + 1101, , "Sheet KHAI THUE dang duoc bao ve."

    oldCalculation = Application.Calculation
    oldScreenUpdating = Application.ScreenUpdating
    oldEnableEvents = Application.EnableEvents
    appStateSaved = True
    Application.ScreenUpdating = False
    Application.EnableEvents = False
    Application.Calculation = xlCalculationManual

    lastK = LastInvoiceRow(wsK)
    If lastK < FIRST_KHAI_ROW Then Err.Raise vbObjectError + 1102, , "Sheet KHAI THUE khong co dong du lieu."
    totalAmountBefore = SumInvoiceColumn(wsK, lastK, K_COL_AMOUNT)
    totalVatBefore = SumInvoiceColumn(wsK, lastK, K_COL_VAT)
    If CREATE_BACKUP_COPY Then backupPath = SaveBackupCopy(wb)

    RoundQuantityAndPrice wsK, lastK, quantityRounded, priceRounded
    ExtendTaxRateValidation wsK, lastK
    wsK.Calculate
    AuditWorkbook wb, wsK, lastK, dataRows, quantityIssues, priceIssues, _
        formulaErrors, placeholderCount, totalAmount, totalVat
    If Abs(totalAmount - totalAmountBefore) > 0.5 Or _
       Abs(totalVat - totalVatBefore) > 0.5 Then
        Err.Raise vbObjectError + 1150, , "Tong M/O thay doi sau khi chi lam tron K/L."
    End If

    runStatus = "THANH_CONG"
    If quantityIssues > 0 Or priceIssues > 0 Or formulaErrors > 0 Or _
       placeholderCount > 0 Then
        runStatus = "HOAN_TAT_CO_CANH_BAO"
    End If
    AppendRunLog wb, "LAM_TRON_K_L", runStatus, dataRows, 0, 0, 0, 0, 0, 0, _
        quantityRounded, priceRounded, 0, 0, quantityIssues, priceIssues, _
        formulaErrors, placeholderCount, totalAmount, totalVat, backupPath
    succeeded = True

CleanExit:
    On Error Resume Next
    If appStateSaved Then
        Application.StatusBar = False
        Application.Calculation = oldCalculation
        Application.ScreenUpdating = oldScreenUpdating
        Application.EnableEvents = oldEnableEvents
    End If
    On Error GoTo 0

    If succeeded Then
        MsgBox _
            "Da lam tron xong." & vbCrLf & _
            "- O So luong thay doi: " & Format$(quantityRounded, "#,##0") & vbCrLf & _
            "- O Don gia thay doi: " & Format$(priceRounded, "#,##0") & vbCrLf & _
            "- Loi do chinh xac con lai K/L: " & Format$(quantityIssues, "#,##0") & "/" & Format$(priceIssues, "#,##0") & vbCrLf & _
            "- Tong M/O duoc giu nguyen: " & Format$(totalAmount, "#,##0") & "/" & Format$(totalVat, "#,##0") & vbCrLf & vbCrLf & _
            BackupMessage(backupPath), _
            vbInformation, "Lam tron hoan tat"
    Else
        MsgBox errorText, vbCritical, "Khong the lam tron"
    End If
    Exit Sub

Fail:
    errorText = "Loi " & CStr(Err.Number) & ": " & Err.Description
    Resume CleanExit
End Sub

' Chi kiem tra, khong chen dong va khong sua K/L. Sheet nhat ky se duoc cap nhat.
Public Sub KiemTraToanBoKhaiThue()
    Dim wb As Workbook, wsK As Worksheet
    Dim lastK As Long, dataRows As Long
    Dim quantityIssues As Long, priceIssues As Long
    Dim formulaErrors As Long, placeholderCount As Long
    Dim totalAmount As Double, totalVat As Double
    Dim runStatus As String

    On Error GoTo Fail
    Set wb = GetTargetWorkbook()
    Set wsK = FindWorksheet(wb, KHAI_SHEET_1, KHAI_SHEET_2)
    If wsK Is Nothing Then Err.Raise vbObjectError + 1200, , "Khong tim thay sheet KHAI THUE."
    DetectKhaiLayout wsK

    lastK = LastInvoiceRow(wsK)
    If lastK < FIRST_KHAI_ROW Then Err.Raise vbObjectError + 1201, , "Sheet KHAI THUE khong co dong du lieu."

    wsK.Calculate
    AuditWorkbook wb, wsK, lastK, dataRows, quantityIssues, priceIssues, _
        formulaErrors, placeholderCount, totalAmount, totalVat
    runStatus = "HOAN_TAT"
    If quantityIssues > 0 Or priceIssues > 0 Or formulaErrors > 0 Or _
       placeholderCount > 0 Then
        runStatus = "HOAN_TAT_CO_CANH_BAO"
    End If
    AppendRunLog wb, "KIEM_TRA", runStatus, dataRows, 0, 0, 0, 0, 0, 0, _
        0, 0, 0, 0, quantityIssues, priceIssues, formulaErrors, placeholderCount, _
        totalAmount, totalVat, vbNullString

    MsgBox _
        "Ket qua kiem tra:" & vbCrLf & _
        "- Dong du lieu: " & Format$(dataRows, "#,##0") & vbCrLf & _
        "- O K chua dung 2 so le/khong hop le: " & Format$(quantityIssues, "#,##0") & vbCrLf & _
        "- O L chua la so nguyen/khong hop le: " & Format$(priceIssues, "#,##0") & vbCrLf & _
        "- Loi cong thuc trong workbook: " & Format$(formulaErrors, "#,##0") & vbCrLf & _
        "- Placeholder 1916 trong KHAI THUE I:O: " & Format$(placeholderCount, "#,##0") & vbCrLf & _
        "- Tong truoc thue: " & Format$(totalAmount, "#,##0") & vbCrLf & _
        "- Tong thue: " & Format$(totalVat, "#,##0") & vbCrLf & vbCrLf & _
        "Chi tiet da ghi vao sheet '" & LOG_SHEET_NAME & "'.", _
        vbInformation, "Kiem tra KHAI THUE"
    Exit Sub

Fail:
    MsgBox "Loi " & CStr(Err.Number) & ": " & Err.Description, vbCritical, "Khong the kiem tra"
End Sub

' Mo rong data validation cot thue suat N den dong hoa don cuoi.
Private Sub ExtendTaxRateValidation(ByVal wsK As Worksheet, ByVal lastK As Long)
    If lastK < FIRST_KHAI_ROW Then Exit Sub
    On Error Resume Next
    wsK.Cells(FIRST_KHAI_ROW, K_COL_RATE).Copy
    wsK.Range(wsK.Cells(FIRST_KHAI_ROW, K_COL_RATE), _
              wsK.Cells(lastK, K_COL_RATE)).PasteSpecial Paste:=xlPasteValidation
    Application.CutCopyMode = False
    On Error GoTo 0
End Sub

Private Sub RoundQuantityAndPrice( _
    ByVal wsK As Worksheet, _
    ByVal lastK As Long, _
    ByRef quantityRounded As Long, _
    ByRef priceRounded As Long)

    Dim r As Long, sourceValue As Variant, roundedValue As Double

    quantityRounded = 0
    priceRounded = 0

    For r = FIRST_KHAI_ROW To lastK
        If Len(Trim$(SafeText(wsK.Cells(r, K_COL_NUMBER).Value2))) > 0 Then
            If Not wsK.Cells(r, K_COL_QTY).HasFormula Then
                sourceValue = wsK.Cells(r, K_COL_QTY).Value2
                If Not IsBlankValue(sourceValue) And IsNumeric(sourceValue) Then
                    roundedValue = Application.WorksheetFunction.Round(CDbl(sourceValue), QTY_DECIMALS)
                    If VarType(sourceValue) = vbString Or _
                       Abs(CDbl(sourceValue) - roundedValue) > 0.000000001 Then
                        wsK.Cells(r, K_COL_QTY).Value2 = roundedValue
                        quantityRounded = quantityRounded + 1
                    End If
                End If
            End If

            If Not wsK.Cells(r, K_COL_PRICE).HasFormula Then
                sourceValue = wsK.Cells(r, K_COL_PRICE).Value2
                If Not IsBlankValue(sourceValue) And IsNumeric(sourceValue) Then
                    roundedValue = Application.WorksheetFunction.Round(CDbl(sourceValue), PRICE_DECIMALS)
                    If VarType(sourceValue) = vbString Or _
                       Abs(CDbl(sourceValue) - roundedValue) > 0.000000001 Then
                        wsK.Cells(r, K_COL_PRICE).Value2 = roundedValue
                        priceRounded = priceRounded + 1
                    End If
                End If
            End If
        End If
    Next r

    If lastK >= FIRST_KHAI_ROW Then
        wsK.Range(wsK.Cells(FIRST_KHAI_ROW, K_COL_QTY), _
                  wsK.Cells(lastK, K_COL_QTY)).NumberFormat = "#,##0.00"
        wsK.Range(wsK.Cells(FIRST_KHAI_ROW, K_COL_PRICE), _
                  wsK.Cells(lastK, K_COL_PRICE)).NumberFormat = "#,##0"
    End If
End Sub

Private Sub AuditWorkbook( _
    ByVal wb As Workbook, _
    ByVal wsK As Worksheet, _
    ByVal lastK As Long, _
    ByRef dataRows As Long, _
    ByRef quantityIssues As Long, _
    ByRef priceIssues As Long, _
    ByRef formulaErrors As Long, _
    ByRef placeholderCount As Long, _
    ByRef totalAmount As Double, _
    ByRef totalVat As Double)

    Dim r As Long, c As Long
    Dim sourceValue As Variant, roundedValue As Double
    Dim ws As Worksheet, errorCells As Range

    dataRows = 0
    quantityIssues = 0
    priceIssues = 0
    formulaErrors = 0
    placeholderCount = 0
    totalAmount = 0#
    totalVat = 0#

    For r = FIRST_KHAI_ROW To lastK
        If Len(Trim$(SafeText(wsK.Cells(r, K_COL_NUMBER).Value2))) > 0 Then
            dataRows = dataRows + 1

            sourceValue = wsK.Cells(r, K_COL_QTY).Value2
            If Not IsBlankValue(sourceValue) Then
                If VarType(sourceValue) = vbString Then
                    quantityIssues = quantityIssues + 1
                ElseIf IsNumeric(sourceValue) Then
                    roundedValue = Application.WorksheetFunction.Round(CDbl(sourceValue), QTY_DECIMALS)
                    If Abs(CDbl(sourceValue) - roundedValue) > 0.000000001 Then
                        quantityIssues = quantityIssues + 1
                    End If
                Else
                    quantityIssues = quantityIssues + 1
                End If
            End If

            sourceValue = wsK.Cells(r, K_COL_PRICE).Value2
            If Not IsBlankValue(sourceValue) Then
                If VarType(sourceValue) = vbString Then
                    priceIssues = priceIssues + 1
                ElseIf IsNumeric(sourceValue) Then
                    roundedValue = Application.WorksheetFunction.Round(CDbl(sourceValue), PRICE_DECIMALS)
                    If Abs(CDbl(sourceValue) - roundedValue) > 0.000000001 Then
                        priceIssues = priceIssues + 1
                    End If
                Else
                    priceIssues = priceIssues + 1
                End If
            End If

            sourceValue = wsK.Cells(r, K_COL_AMOUNT).Value2
            If Not IsBlankValue(sourceValue) And IsNumeric(sourceValue) Then
                totalAmount = totalAmount + CDbl(sourceValue)
            End If

            sourceValue = wsK.Cells(r, K_COL_VAT).Value2
            If Not IsBlankValue(sourceValue) And IsNumeric(sourceValue) Then
                totalVat = totalVat + CDbl(sourceValue)
            End If

            For c = K_COL_ITEM To K_COL_VAT
                sourceValue = wsK.Cells(r, c).Value2
                If VarType(sourceValue) = vbString Then
                    If Trim$(CStr(sourceValue)) = "1916" Then
                        placeholderCount = placeholderCount + 1
                    End If
                End If
            Next c
        End If
    Next r

    For Each ws In wb.Worksheets
        Set errorCells = Nothing
        On Error Resume Next
        Set errorCells = ws.UsedRange.SpecialCells(xlCellTypeFormulas, xlErrors)
        On Error GoTo 0
        If Not errorCells Is Nothing Then formulaErrors = formulaErrors + errorCells.CountLarge
    Next ws
    formulaErrors = formulaErrors + _
        CountTotalFormulaMismatches(wsK, lastK, totalAmount, totalVat)
End Sub

Private Sub AppendRunLog( _
    ByVal wb As Workbook, _
    ByVal actionName As String, _
    ByVal runStatus As String, _
    ByVal dataRows As Long, _
    ByVal exactMatches As Long, _
    ByVal fallbackMatches As Long, _
    ByVal expandedGroups As Long, _
    ByVal addedRows As Long, _
    ByVal singleRowsFilled As Long, _
    ByVal cellsFilled As Long, _
    ByVal quantityRounded As Long, _
    ByVal priceRounded As Long, _
    ByVal unmatchedGroups As Long, _
    ByVal ambiguousGroups As Long, _
    ByVal quantityIssues As Long, _
    ByVal priceIssues As Long, _
    ByVal formulaErrors As Long, _
    ByVal placeholderCount As Long, _
    ByVal totalAmount As Double, _
    ByVal totalVat As Double, _
    ByVal backupPath As String)

    Dim ws As Worksheet, logRow As Long, i As Long
    Dim headerRange As Range
    Dim headers As Variant, values As Variant
    Dim logError As String

    On Error GoTo LogFail
    Set ws = GetOrCreateLogSheet(wb)
    If ws Is Nothing Then Err.Raise vbObjectError + 1500, , "Khong tao duoc sheet nhat ky."

    headers = Array( _
        "Thoi_gian", "Workbook", "Hanh_dong", "Trang_thai", "Dong_du_lieu", _
        "Khop_day_du", "Khop_fallback", "Hoa_don_bung", "Dong_them", _
        "Dong_dien_o_trong", "O_da_dien", "SL_da_lam_tron", _
        "Don_gia_da_lam_tron", "Nhom_khong_khop", "Nhom_mo_ho", _
        "Loi_K", "Loi_L", "Loi_cong_thuc", "Placeholder_1916", _
        "Tong_truoc_thue", "Tong_thue", "Duong_dan_backup")

    values = Array( _
        Now, wb.Name, actionName, runStatus, dataRows, exactMatches, fallbackMatches, _
        expandedGroups, addedRows, singleRowsFilled, cellsFilled, quantityRounded, _
        priceRounded, unmatchedGroups, ambiguousGroups, quantityIssues, priceIssues, _
        formulaErrors, placeholderCount, totalAmount, totalVat, backupPath)

    If Len(Trim$(SafeText(ws.Cells(1, 1).Value2))) = 0 Then
        For i = LBound(headers) To UBound(headers)
            ws.Cells(1, i + 1).Value2 = headers(i)
        Next i
        Set headerRange = ws.Range(ws.Cells(1, 1), ws.Cells(1, UBound(headers) + 1))
        headerRange.Font.Bold = True
        headerRange.Interior.Color = RGB(217, 225, 242)
        headerRange.AutoFilter
    End If

    logRow = ws.Cells(ws.rows.Count, 1).End(xlUp).Row + 1
    If logRow < 2 Then logRow = 2
    For i = LBound(values) To UBound(values)
        ws.Cells(logRow, i + 1).value = values(i)
    Next i

    ws.Columns(1).NumberFormat = "yyyy-mm-dd hh:mm:ss"
    ws.Columns(20).NumberFormat = "#,##0"
    ws.Columns(21).NumberFormat = "#,##0"
    ws.Range(ws.Cells(1, 1), ws.Cells(logRow, UBound(headers) + 1)).Columns.AutoFit
    For i = 1 To UBound(headers) + 1
        If ws.Columns(i).ColumnWidth > 32 Then ws.Columns(i).ColumnWidth = 32
    Next i
    Exit Sub
LogFail:
    logError = Err.Description
    Err.Raise vbObjectError + 1501, "AppendRunLog", _
        "Khong the ghi sheet '" & LOG_SHEET_NAME & "': " & logError
End Sub

Private Sub AddIssueEntry( _
    ByVal issueRows As Collection, _
    ByVal khaiKey As String, _
    ByVal detailKey As String, _
    ByVal actionName As String, _
    ByVal reasonText As String, _
    ByVal khaiRowCount As Long, _
    ByVal detailRowCount As Long)

    If issueRows Is Nothing Then Exit Sub
    If issueRows.Count >= MAX_ISSUE_LOG_ROWS Then Exit Sub
    issueRows.Add Array(khaiKey, detailKey, actionName, reasonText, _
                        khaiRowCount, detailRowCount)
End Sub

Private Sub AppendIssueLog( _
    ByVal wb As Workbook, _
    ByVal runId As String, _
    ByVal issueRows As Collection)

    Dim ws As Worksheet, headerRange As Range
    Dim headers As Variant, entry As Variant
    Dim logRow As Long, i As Long, j As Long
    Dim logError As String

    On Error GoTo LogFail
    Set ws = GetOrCreateIssueSheet(wb)
    headers = Array("Thoi_gian", "Run_ID", "Khoa_KHAI", "Khoa_CHI_TIET", _
                    "Hanh_dong", "Ly_do", "So_dong_KHAI", "So_dong_CHI_TIET")

    If Len(Trim$(SafeText(ws.Cells(1, 1).Value2))) = 0 Then
        For i = LBound(headers) To UBound(headers)
            ws.Cells(1, i + 1).Value2 = headers(i)
        Next i
        Set headerRange = ws.Range(ws.Cells(1, 1), ws.Cells(1, UBound(headers) + 1))
        headerRange.Font.Bold = True
        headerRange.Interior.Color = RGB(255, 235, 156)
        headerRange.AutoFilter
    End If

    logRow = ws.Cells(ws.rows.Count, 1).End(xlUp).Row + 1
    If logRow < 2 Then logRow = 2
    For i = 1 To issueRows.Count
        entry = issueRows(i)
        ws.Cells(logRow, 1).value = Now
        ws.Cells(logRow, 2).Value2 = runId
        For j = LBound(entry) To UBound(entry)
            ws.Cells(logRow, j + 3).Value2 = entry(j)
        Next j
        logRow = logRow + 1
    Next i

    ws.Columns(1).NumberFormat = "yyyy-mm-dd hh:mm:ss"
    ws.Range(ws.Cells(1, 1), ws.Cells(logRow - 1, UBound(headers) + 1)).Columns.AutoFit
    For i = 1 To UBound(headers) + 1
        If ws.Columns(i).ColumnWidth > 42 Then ws.Columns(i).ColumnWidth = 42
    Next i
    Exit Sub
LogFail:
    logError = Err.Description
    Err.Raise vbObjectError + 1511, "AppendIssueLog", _
        "Khong the ghi sheet '" & ISSUE_SHEET_NAME & "': " & logError
End Sub

Private Function GetOrCreateLogSheet(ByVal wb As Workbook) As Worksheet
    Dim ws As Worksheet

    For Each ws In wb.Worksheets
        If StrComp(Trim$(ws.Name), LOG_SHEET_NAME, vbTextCompare) = 0 Then
            Set GetOrCreateLogSheet = ws
            Exit Function
        End If
    Next ws

    Set ws = wb.Worksheets.Add(After:=wb.Worksheets(wb.Worksheets.Count))
    ws.Name = LOG_SHEET_NAME
    Set GetOrCreateLogSheet = ws
End Function

Private Function GetOrCreateIssueSheet(ByVal wb As Workbook) As Worksheet
    Dim ws As Worksheet

    For Each ws In wb.Worksheets
        If StrComp(Trim$(ws.Name), ISSUE_SHEET_NAME, vbTextCompare) = 0 Then
            Set GetOrCreateIssueSheet = ws
            Exit Function
        End If
    Next ws

    Set ws = wb.Worksheets.Add(After:=wb.Worksheets(wb.Worksheets.Count))
    ws.Name = ISSUE_SHEET_NAME
    Set GetOrCreateIssueSheet = ws
End Function

Private Function SumInvoiceColumn( _
    ByVal wsK As Worksheet, _
    ByVal lastK As Long, _
    ByVal columnNumber As Long) As Double

    Dim r As Long, sourceValue As Variant

    For r = FIRST_KHAI_ROW To lastK
        If Len(Trim$(SafeText(wsK.Cells(r, K_COL_NUMBER).Value2))) > 0 Then
            sourceValue = wsK.Cells(r, columnNumber).Value2
            If Not IsError(sourceValue) And Not IsBlankValue(sourceValue) Then
                If IsNumeric(sourceValue) Then
                    SumInvoiceColumn = SumInvoiceColumn + CDbl(sourceValue)
                End If
            End If
        End If
    Next r
End Function

Private Function FindTotalFormulaCell( _
    ByVal wsK As Worksheet, _
    ByVal lastK As Long, _
    ByVal columnNumber As Long) As Range

    Dim r As Long, searchEnd As Long

    searchEnd = lastK + 30
    If searchEnd > wsK.rows.Count Then searchEnd = wsK.rows.Count
    For r = lastK + 1 To searchEnd
        If wsK.Cells(r, columnNumber).HasFormula Then
            Set FindTotalFormulaCell = wsK.Cells(r, columnNumber)
            Exit Function
        End If
    Next r
End Function

' Neu o tong dang la SUM don gian, mo rong den dong hoa don cuoi cung moi.
' Khong ghi de cac cong thuc SUBTOTAL/SUMIFS tuy bien cua nguoi dung.
Private Sub UpdateSimpleTotalFormula( _
    ByVal wsK As Worksheet, _
    ByVal lastK As Long, _
    ByVal columnNumber As Long)

    Dim totalCell As Range, formulaText As String

    Set totalCell = FindTotalFormulaCell(wsK, lastK, columnNumber)
    If totalCell Is Nothing Then Exit Sub

    formulaText = UCase$(Replace(Replace(totalCell.Formula, " ", ""), "$", ""))
    If IsSimpleSameColumnSum(formulaText, columnNumber) Then
        totalCell.FormulaR1C1 = "=SUM(R" & FIRST_KHAI_ROW & "C" & columnNumber & _
            ":R" & lastK & "C" & columnNumber & ")"
    End If
End Sub

Private Function IsSimpleSameColumnSum( _
    ByVal formulaText As String, _
    ByVal columnNumber As Long) As Boolean

    Dim insideText As String, parts As Variant
    Dim expectedColumn As String
    Dim startColumn As String, endColumn As String
    Dim startRow As String, endRow As String

    If Left$(formulaText, 5) <> "=SUM(" Then Exit Function
    If Right$(formulaText, 1) <> ")" Then Exit Function

    insideText = Mid$(formulaText, 6, Len(formulaText) - 6)
    parts = Split(insideText, ":")
    If UBound(parts) <> 1 Then Exit Function

    expectedColumn = ColumnLetterFromNumber(columnNumber)
    startColumn = LeadingLetters(CStr(parts(0)))
    endColumn = LeadingLetters(CStr(parts(1)))
    startRow = Mid$(CStr(parts(0)), Len(startColumn) + 1)
    endRow = Mid$(CStr(parts(1)), Len(endColumn) + 1)

    If startColumn <> expectedColumn Or endColumn <> expectedColumn Then Exit Function
    If Not IsDigitsOnly(startRow) Or Not IsDigitsOnly(endRow) Then Exit Function
    If CLng(startRow) <> FIRST_KHAI_ROW Then Exit Function
    If CLng(endRow) < FIRST_KHAI_ROW Then Exit Function
    IsSimpleSameColumnSum = True
End Function

Private Function ColumnLetterFromNumber(ByVal columnNumber As Long) As String
    Dim n As Long

    n = columnNumber
    Do While n > 0
        n = n - 1
        ColumnLetterFromNumber = Chr$(65 + (n Mod 26)) & ColumnLetterFromNumber
        n = n \ 26
    Loop
End Function

Private Function LeadingLetters(ByVal addressText As String) As String
    Dim i As Long, ch As String

    For i = 1 To Len(addressText)
        ch = Mid$(addressText, i, 1)
        If ch < "A" Or ch > "Z" Then Exit For
        LeadingLetters = LeadingLetters & ch
    Next i
End Function

Private Sub UpdateStaticDuplicateTotals( _
    ByVal wsK As Worksheet, _
    ByVal lastK As Long, _
    ByVal columnNumber As Long, _
    ByVal totalBefore As Double, _
    ByVal totalAfter As Double)

    Dim r As Long, searchEnd As Long, sourceValue As Variant

    If Abs(totalBefore) < 0.0000001 Then Exit Sub
    searchEnd = lastK + 40
    If searchEnd > wsK.rows.Count Then searchEnd = wsK.rows.Count
    For r = lastK + 1 To searchEnd
        If Not wsK.Cells(r, columnNumber).HasFormula Then
            sourceValue = wsK.Cells(r, columnNumber).Value2
            If Not IsError(sourceValue) And Not IsBlankValue(sourceValue) Then
                If IsNumeric(sourceValue) Then
                    If Abs(CDbl(sourceValue) - totalBefore) <= 0.5 Then
                        wsK.Cells(r, columnNumber).Value2 = totalAfter
                    End If
                End If
            End If
        End If
    Next r
End Sub

Private Function CountTotalFormulaMismatches( _
    ByVal wsK As Worksheet, _
    ByVal lastK As Long, _
    ByVal totalAmount As Double, _
    ByVal totalVat As Double) As Long

    Dim totalCell As Range, sourceValue As Variant

    Set totalCell = FindTotalFormulaCell(wsK, lastK, K_COL_AMOUNT)
    If Not totalCell Is Nothing Then
        sourceValue = totalCell.Value2
        If Not IsError(sourceValue) Then
            If Not IsNumeric(sourceValue) Then
                CountTotalFormulaMismatches = CountTotalFormulaMismatches + 1
            ElseIf Abs(CDbl(sourceValue) - totalAmount) > 0.5 Then
                CountTotalFormulaMismatches = CountTotalFormulaMismatches + 1
            End If
        End If
    End If

    Set totalCell = FindTotalFormulaCell(wsK, lastK, K_COL_VAT)
    If Not totalCell Is Nothing Then
        sourceValue = totalCell.Value2
        If Not IsError(sourceValue) Then
            If Not IsNumeric(sourceValue) Then
                CountTotalFormulaMismatches = CountTotalFormulaMismatches + 1
            ElseIf Abs(CDbl(sourceValue) - totalVat) > 0.5 Then
                CountTotalFormulaMismatches = CountTotalFormulaMismatches + 1
            End If
        End If
    End If
End Function

Private Function SaveBackupCopy(ByVal wb As Workbook) As String
    Dim dotPosition As Long
    Dim fileStem As String, extension As String, backupPath As String
    Dim baseBackupPath As String, sequence As Long

    If Len(wb.Path) = 0 Then
        Err.Raise vbObjectError + 1400, , _
            "Workbook chua duoc luu. Hay Save As file .xlsm truoc khi chay macro."
    End If
    If wb.ReadOnly Then
        Err.Raise vbObjectError + 1401, , _
            "Workbook dang Read-only, khong the tao backup va cap nhat an toan."
    End If

    dotPosition = InStrRev(wb.Name, ".")
    If dotPosition > 1 Then
        fileStem = Left$(wb.Name, dotPosition - 1)
        extension = Mid$(wb.Name, dotPosition)
    Else
        fileStem = wb.Name
        extension = ".xlsm"
    End If

    baseBackupPath = wb.Path & Application.PathSeparator & fileStem & _
        "_backup_" & Format$(Now, "yyyymmdd_hhnnss") & extension
    backupPath = baseBackupPath
    Do While Len(Dir$(backupPath, vbNormal Or vbHidden Or vbSystem Or vbReadOnly)) > 0
        sequence = sequence + 1
        backupPath = Left$(baseBackupPath, Len(baseBackupPath) - Len(extension)) & _
            "_" & Format$(sequence, "000") & extension
    Loop

    wb.SaveCopyAs backupPath
    SaveBackupCopy = backupPath
End Function

Private Function BackupMessage(ByVal backupPath As String) As String
    If Len(backupPath) > 0 Then
        BackupMessage = "Ban sao truoc khi sua: " & backupPath & vbCrLf & vbCrLf
    Else
        BackupMessage = "Workbook chua co duong dan nen macro khong tao ban sao tu dong." & _
            vbCrLf & vbCrLf
    End If
End Function

Private Function FillHeaderBlanksCertain( _
    ByVal wsK As Worksheet, _
    ByVal khaiRows As Collection, _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection) As Long

    Dim khaiCols As Variant, detailCols As Variant
    Dim i As Long, j As Long, rawValue As Variant
    Dim destination As Range

    khaiCols = Array(K_COL_MODEL, K_COL_SERIES, K_COL_NUMBER, _
                     K_COL_DATE, K_COL_VENDOR, K_COL_TAX_ID)
    detailCols = Array(D_COL_MODEL, D_COL_SERIES, D_COL_NUMBER, _
                      D_COL_DATE, D_COL_VENDOR, D_COL_TAX_ID)

    For i = LBound(khaiCols) To UBound(khaiCols)
        If TryUniqueDetailValue(wsD, detailRows, CLng(detailCols(i)), rawValue) Then
            For j = 1 To khaiRows.Count
                Set destination = wsK.Cells(CLng(khaiRows(j)), CLng(khaiCols(i)))
                If IsBlankValue(destination.Value2) And Not destination.HasFormula Then
                    destination.Value2 = rawValue
                    FillHeaderBlanksCertain = FillHeaderBlanksCertain + 1
                End If
            Next j
        End If
    Next i
End Function

Private Function TryUniqueDetailValue( _
    ByVal ws As Worksheet, _
    ByVal sourceRows As Collection, _
    ByVal sourceCol As Long, _
    ByRef rawResult As Variant) As Boolean

    Dim i As Long, sourceValue As Variant
    Dim signature As String, firstSignature As String
    Dim foundValue As Boolean

    If sourceCol <= 0 Then Exit Function

    For i = 1 To sourceRows.Count
        sourceValue = ws.Cells(CLng(sourceRows(i)), sourceCol).Value2
        If Not IsSourceMissing(sourceValue) Then
            Select Case sourceCol
                Case D_COL_DATE
                    signature = NormalizeDateKey(sourceValue)
                Case D_COL_NUMBER
                    signature = NormalizeInvoiceNumber(sourceValue)
                Case D_COL_MODEL, D_COL_SERIES
                    signature = CompactKeyPart(sourceValue)
                Case D_COL_TAX_ID
                    signature = NormalizeTaxId(sourceValue)
                Case Else
                    signature = NormalizeLooseText(sourceValue)
            End Select

            If Not foundValue Then
                rawResult = sourceValue
                firstSignature = signature
                foundValue = True
            ElseIf StrComp(firstSignature, signature, vbTextCompare) <> 0 Then
                Exit Function
            End If
        End If
    Next i

    TryUniqueDetailValue = foundValue
End Function

' Chi lay dien giai tong hop khi tat ca gia tri khac rong trong nhom deu giong nhau.
' Tach rieng khoi TryUniqueDetailValue vi so thu tu cot cua hai sheet co the trung
' nhau nhung mang y nghia khac nhau.
Private Function TryUniqueSummaryValue( _
    ByVal ws As Worksheet, _
    ByVal sourceRows As Collection, _
    ByVal sourceCol As Long, _
    ByRef rawResult As Variant) As Boolean

    Dim i As Long, sourceValue As Variant
    Dim signature As String, firstSignature As String
    Dim foundValue As Boolean

    If sourceCol <= 0 Then Exit Function

    For i = 1 To sourceRows.Count
        sourceValue = ws.Cells(CLng(sourceRows(i)), sourceCol).Value2
        If Not IsSourceMissing(sourceValue) Then
            signature = NormalizeLooseText(sourceValue)
            If Not foundValue Then
                rawResult = sourceValue
                firstSignature = signature
                foundValue = True
            ElseIf StrComp(firstSignature, signature, vbTextCompare) <> 0 Then
                rawResult = Empty
                Exit Function
            End If
        End If
    Next i

    TryUniqueSummaryValue = foundValue
End Function

' Dien cac o trong I:J:K:L theo thu tu chi tiet -> tong hop.
' Chi tu dong dien khi nhom dich co dung 1 dong va nguon duoc chon cung dung
' 1 dong. Dieu kien nay ngan viec phat mot gia tri cho nhieu dong hoa don.
' Nhom da bi quy trinh doi chieu danh dau BLOCKED se luon duoc bo qua.
Private Function FillBlankIjklCertain( _
    ByVal wsK As Worksheet, _
    ByVal wsD As Worksheet, _
    ByVal wsS As Worksheet, _
    ByVal khaiGroups As Object, _
    ByVal detailGroups As Object, _
    ByVal summaryGroups As Object, _
    ByVal resolvedKeys As Object, _
    ByVal blockedKeys As Object, _
    ByVal stats As Object) As Long

    Dim khaiCols As Variant, detailCols As Variant, summaryCols As Variant
    Dim decimalPlaces As Variant, statNames As Variant
    Dim key As Variant, kKey As String, dKey As String, summaryKey As String
    Dim khaiRows As Collection, detailRows As Collection, summaryRows As Collection
    Dim fieldIndex As Long
    Dim rawValue As Variant, outputValue As Variant
    Dim detailUnique As Boolean, summaryUnique As Boolean
    Dim detailConflict As Boolean, summaryConflict As Boolean
    Dim detailAllowed As Boolean, summaryAllowed As Boolean
    Dim sourceName As String
    Dim destination As Range

    khaiCols = Array(K_COL_ITEM, K_COL_UNIT, K_COL_QTY, K_COL_PRICE)
    detailCols = Array(D_COL_ITEM, D_COL_UNIT, D_COL_QTY, D_COL_PRICE)
    summaryCols = Array(S_COL_ITEM, S_COL_UNIT, S_COL_QTY, S_COL_PRICE)
    decimalPlaces = Array(-1, -1, QTY_DECIMALS, PRICE_DECIMALS)
    statNames = Array("ITEM", "UNIT", "QTY", "PRICE")

    For Each key In khaiGroups.Keys
        kKey = CStr(key)

        If Not blockedKeys Is Nothing Then
            If blockedKeys.Exists(kKey) Then
                AddStat stats, "BLOCKED", 1
                GoTo NextKey
            End If
        End If

        Set khaiRows = khaiGroups(kKey)
        If khaiRows.Count <> 1 Then
            AddStat stats, "SKIPPED_MULTI_KHAI", 1
            AddStat stats, "AMBIGUOUS", 1
            GoTo NextKey
        End If

        ' Macro rieng dung exact key. Trong quy trinh day du, chi dung chi tiet
        ' khi khoa da duoc resolve; khoa chua resolve chi duoc phep thu tong hop.
        dKey = vbNullString
        If resolvedKeys Is Nothing Then
            dKey = kKey
        ElseIf resolvedKeys.Exists(kKey) Then
            dKey = CStr(resolvedKeys(kKey))
        End If

        detailAllowed = False
        If Len(dKey) > 0 Then
            If detailGroups.Exists(dKey) Then
                Set detailRows = detailGroups(dKey)
                If detailRows.Count = 1 Then
                    detailAllowed = True
                Else
                    AddStat stats, "SKIPPED_MULTI_DETAIL", 1
                    AddStat stats, "AMBIGUOUS", 1
                    GoTo NextKey
                End If
            End If
        End If

        summaryKey = kKey
        If Len(dKey) > 0 Then
            If summaryGroups.Exists(dKey) Then summaryKey = dKey
        End If

        summaryAllowed = False
        If CanUseKhaiFull(wsK, CLng(khaiRows(1))) And _
           summaryGroups.Exists(summaryKey) Then
            Set summaryRows = summaryGroups(summaryKey)
            If summaryRows.Count = 1 And _
               CanUseSummaryFull(wsS, CLng(summaryRows(1))) Then
                summaryAllowed = True
            Else
                AddStat stats, "SKIPPED_MULTI_SUMMARY", 1
                AddStat stats, "AMBIGUOUS", 1
            End If
        End If

        For fieldIndex = LBound(khaiCols) To UBound(khaiCols)
            rawValue = Empty
            sourceName = vbNullString
            detailConflict = False
            summaryConflict = False
            detailUnique = False
            summaryUnique = False

            If detailAllowed And CLng(detailCols(fieldIndex)) > 0 Then
                detailUnique = TryUniqueFieldValue( _
                    wsD, detailRows, CLng(detailCols(fieldIndex)), _
                    CLng(decimalPlaces(fieldIndex)), rawValue, detailConflict)
            End If

            If detailConflict Then
                AddStat stats, "AMBIGUOUS", 1
                GoTo NextField
            ElseIf detailUnique Then
                sourceName = "DETAIL"
            ElseIf summaryAllowed And CLng(summaryCols(fieldIndex)) > 0 Then
                summaryUnique = TryUniqueFieldValue( _
                    wsS, summaryRows, CLng(summaryCols(fieldIndex)), _
                    CLng(decimalPlaces(fieldIndex)), rawValue, summaryConflict)
                If summaryConflict Then
                    AddStat stats, "AMBIGUOUS", 1
                    GoTo NextField
                ElseIf summaryUnique Then
                    sourceName = "SUMMARY"
                End If
            End If

            If Len(sourceName) > 0 And Not IsSourceMissing(rawValue) Then
                outputValue = FieldValueForOutput( _
                    rawValue, CLng(decimalPlaces(fieldIndex)))
                Set destination = wsK.Cells( _
                    CLng(khaiRows(1)), CLng(khaiCols(fieldIndex)))
                If IsBlankValue(destination.Value2) And _
                   Not destination.HasFormula Then
                    destination.Value2 = outputValue
                    FillBlankIjklCertain = FillBlankIjklCertain + 1
                    AddStat stats, _
                        CStr(statNames(fieldIndex)) & "_" & sourceName, 1
                End If
            End If
NextField:
        Next fieldIndex
NextKey:
    Next key
End Function

Private Function TryUniqueFieldValue( _
    ByVal ws As Worksheet, _
    ByVal sourceRows As Collection, _
    ByVal sourceCol As Long, _
    ByVal decimals As Long, _
    ByRef rawResult As Variant, _
    ByRef hasConflict As Boolean) As Boolean

    Dim i As Long, sourceValue As Variant, normalizedValue As Variant
    Dim signature As String, firstSignature As String
    Dim foundValue As Boolean

    If sourceCol <= 0 Then Exit Function

    For i = 1 To sourceRows.Count
        sourceValue = ws.Cells(CLng(sourceRows(i)), sourceCol).Value2
        If Not IsSourceMissing(sourceValue) Then
            normalizedValue = FieldValueForOutput(sourceValue, decimals)
            If decimals >= 0 And IsNumeric(normalizedValue) Then
                signature = "N|" & Format$(CDbl(normalizedValue), "0.############")
            Else
                signature = "T|" & NormalizeLooseText(normalizedValue)
            End If

            If Not foundValue Then
                rawResult = sourceValue
                firstSignature = signature
                foundValue = True
            ElseIf StrComp(firstSignature, signature, vbTextCompare) <> 0 Then
                rawResult = Empty
                hasConflict = True
                Exit Function
            End If
        End If
    Next i

    TryUniqueFieldValue = foundValue
End Function

Private Function FieldValueForOutput( _
    ByVal value As Variant, _
    ByVal decimals As Long) As Variant

    If IsSourceMissing(value) Then
        FieldValueForOutput = Empty
    ElseIf decimals >= 0 And IsNumeric(value) Then
        FieldValueForOutput = _
            Application.WorksheetFunction.Round(CDbl(value), decimals)
    Else
        FieldValueForOutput = value
    End If
End Function

Private Sub AddStat( _
    ByVal stats As Object, _
    ByVal statName As String, _
    ByVal incrementBy As Long)

    If stats.Exists(statName) Then
        stats(statName) = CLng(stats(statName)) + incrementBy
    Else
        stats.Add statName, incrementBy
    End If
End Sub

Private Sub MarkBlockedKey(ByVal blockedKeys As Object, ByVal keyValue As String)
    If blockedKeys Is Nothing Then Exit Sub
    If Not blockedKeys.Exists(keyValue) Then blockedKeys.Add keyValue, True
End Sub

Private Function StatValue(ByVal stats As Object, ByVal statName As String) As Long
    If Not stats Is Nothing Then
        If stats.Exists(statName) Then StatValue = CLng(stats(statName))
    End If
End Function

' Cap nhat rieng cot Ten hang hoa/dich vu theo thu tu:
' chi tiet hoa don -> tong hop hoa don -> giu gia tri KHAI THUE goc.
Private Function ApplyDescriptionPriorityForGroup( _
    ByVal wsK As Worksheet, _
    ByVal khaiRows As Collection, _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection, _
    ByVal summaryItem As Variant) As Long

    Dim i As Long, khaiRow As Long
    Dim detailItem As Variant, uniqueDetailItem As Variant, preferredItem As Variant
    Dim hasUniqueDetailItem As Boolean
    Dim destination As Range

    ' Nhieu dong nguon nhung cung mot Dien giai van la mot gia tri chac chan.
    ' Uu tien nay ngan viec ghep lap cung mot mo ta nhieu lan tren KHAI THUE.
    hasUniqueDetailItem = TryUniqueDetailValue( _
        wsD, detailRows, D_COL_ITEM, uniqueDetailItem)

    For i = 1 To khaiRows.Count
        khaiRow = CLng(khaiRows(i))
        detailItem = Empty
        preferredItem = Empty

        If hasUniqueDetailItem Then
            detailItem = uniqueDetailItem
        ElseIf detailRows.Count = 1 Then
            detailItem = wsD.Cells(CLng(detailRows(1)), D_COL_ITEM).Value2
        Else
            TryFindUniqueDetailItemForKhaiRow _
                wsK, khaiRow, wsD, detailRows, detailItem
        End If

        If Not IsSourceMissing(detailItem) Then
            preferredItem = detailItem
        ElseIf khaiRows.Count = 1 And _
               Not IsSourceMissing(summaryItem) Then
            preferredItem = summaryItem
        End If

        If Not IsSourceMissing(preferredItem) Then
            Set destination = wsK.Cells(khaiRow, K_COL_ITEM)
            If Not destination.HasFormula Then
                If StrComp(NormalizeLooseText(destination.Value2), _
                           NormalizeLooseText(preferredItem), vbTextCompare) <> 0 Then
                    destination.Value2 = preferredItem
                    ApplyDescriptionPriorityForGroup = _
                        ApplyDescriptionPriorityForGroup + 1
                End If
            End If
        End If
    Next i
End Function

Private Function ApplySummaryDescriptionForGroup( _
    ByVal wsK As Worksheet, _
    ByVal khaiRows As Collection, _
    ByVal summaryItem As Variant) As Long

    Dim i As Long
    Dim destination As Range

    If IsSourceMissing(summaryItem) Then Exit Function
    If khaiRows.Count <> 1 Then Exit Function

    For i = 1 To khaiRows.Count
        Set destination = wsK.Cells(CLng(khaiRows(i)), K_COL_ITEM)
        If Not destination.HasFormula Then
            If StrComp(NormalizeLooseText(destination.Value2), _
                       NormalizeLooseText(summaryItem), vbTextCompare) <> 0 Then
                destination.Value2 = summaryItem
                ApplySummaryDescriptionForGroup = _
                    ApplySummaryDescriptionForGroup + 1
            End If
        End If
    Next i
End Function

' Voi hoa don nhieu dong, chi chon dien giai chi tiet khi dung mot dong nguon
' phu hop voi cac thong tin dong KHAI THUE (khong dung dien giai goc de doi chieu).
Private Function TryFindUniqueDetailItemForKhaiRow( _
    ByVal wsK As Worksheet, _
    ByVal khaiRow As Long, _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection, _
    ByRef rawResult As Variant) As Boolean

    Dim i As Long, detailRow As Long
    Dim compatibleCount As Long, selectedRow As Long

    For i = 1 To detailRows.Count
        detailRow = CLng(detailRows(i))
        If DetailRowCompatibleForDescription(wsK, khaiRow, wsD, detailRow) Then
            compatibleCount = compatibleCount + 1
            selectedRow = detailRow
            If compatibleCount > 1 Then Exit For
        End If
    Next i

    If compatibleCount = 1 Then
        rawResult = wsD.Cells(selectedRow, D_COL_ITEM).Value2
        If Not IsSourceMissing(rawResult) Then
            TryFindUniqueDetailItemForKhaiRow = True
        End If
    End If
End Function

Private Function DetailRowCompatibleForDescription( _
    ByVal wsK As Worksheet, _
    ByVal khaiRow As Long, _
    ByVal wsD As Worksheet, _
    ByVal detailRow As Long) As Boolean

    Dim hasConstraint As Boolean
    Dim targetValue As Variant, sourceValue As Variant
    Dim targetNumber As Double, tolerance As Double
    Dim amountMatches As Boolean

    targetValue = wsK.Cells(khaiRow, K_COL_RATE).Value2
    If Not IsSourceMissing(targetValue) Then
        sourceValue = wsD.Cells(detailRow, D_COL_RATE).Value2
        If IsSourceMissing(sourceValue) Then Exit Function
        If StrComp(NormalizeRate(targetValue), NormalizeRate(sourceValue), _
                   vbTextCompare) <> 0 Then Exit Function
        hasConstraint = True
    End If

    targetValue = wsK.Cells(khaiRow, K_COL_UNIT).Value2
    If Not IsSourceMissing(targetValue) Then
        sourceValue = wsD.Cells(detailRow, D_COL_UNIT).Value2
        If IsSourceMissing(sourceValue) Then Exit Function
        If StrComp(NormalizeLooseText(targetValue), NormalizeLooseText(sourceValue), _
                   vbTextCompare) <> 0 Then Exit Function
        hasConstraint = True
    End If

    targetValue = wsK.Cells(khaiRow, K_COL_QTY).Value2
    If Not IsSourceMissing(targetValue) Then
        sourceValue = wsD.Cells(detailRow, D_COL_QTY).Value2
        If IsSourceMissing(sourceValue) Then Exit Function
        If CanonNumber(targetValue, QTY_DECIMALS) <> _
           CanonNumber(sourceValue, QTY_DECIMALS) Then Exit Function
        hasConstraint = True
    End If

    targetValue = wsK.Cells(khaiRow, K_COL_PRICE).Value2
    If Not IsSourceMissing(targetValue) Then
        sourceValue = wsD.Cells(detailRow, D_COL_PRICE).Value2
        If IsSourceMissing(sourceValue) Then Exit Function
        If CanonNumber(targetValue, PRICE_DECIMALS) <> _
           CanonNumber(sourceValue, PRICE_DECIMALS) Then Exit Function
        hasConstraint = True
    End If

    tolerance = RoundingTolerance(1)
    targetValue = wsK.Cells(khaiRow, K_COL_AMOUNT).Value2
    If Not IsSourceMissing(targetValue) Then
        targetNumber = SafeDouble(targetValue)
        amountMatches = False
        sourceValue = wsD.Cells(detailRow, D_COL_TOTAL_GOODS).Value2
        If Not IsSourceMissing(sourceValue) Then
            amountMatches = (Abs(targetNumber - SafeDouble(sourceValue)) <= tolerance)
        End If
        If Not amountMatches Then
            sourceValue = wsD.Cells(detailRow, D_COL_PRETAX).Value2
            If Not IsSourceMissing(sourceValue) Then
                amountMatches = (Abs(targetNumber - SafeDouble(sourceValue)) <= tolerance)
            End If
        End If
        If Not amountMatches Then Exit Function
        hasConstraint = True
    End If

    targetValue = wsK.Cells(khaiRow, K_COL_VAT).Value2
    If Not IsSourceMissing(targetValue) Then
        sourceValue = wsD.Cells(detailRow, D_COL_VAT).Value2
        If IsSourceMissing(sourceValue) Then Exit Function
        If Abs(SafeDouble(targetValue) - SafeDouble(sourceValue)) > tolerance Then
            Exit Function
        End If
        hasConstraint = True
    End If

    DetailRowCompatibleForDescription = hasConstraint
End Function

Private Function CountMaterializedLines( _
    ByVal wsK As Worksheet, _
    ByVal khaiRows As Collection, _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection) As Long

    Dim remaining As Object
    Dim i As Long, signature As String

    Set remaining = NewTextDictionary()
    For i = 1 To detailRows.Count
        signature = DetailLineSignature(wsD, CLng(detailRows(i)))
        If remaining.Exists(signature) Then
            remaining(signature) = CLng(remaining(signature)) + 1
        Else
            remaining.Add signature, 1
        End If
    Next i

    For i = 1 To khaiRows.Count
        signature = KhaiLineSignature(wsK, CLng(khaiRows(i)))
        If remaining.Exists(signature) Then
            If CLng(remaining(signature)) > 0 Then
                remaining(signature) = CLng(remaining(signature)) - 1
                CountMaterializedLines = CountMaterializedLines + 1
            End If
        End If
    Next i
End Function

Private Function KhaiLineSignature(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    KhaiLineSignature = _
        NormalizeLooseText(ws.Cells(rowNumber, K_COL_ITEM).Value2) & "|" & _
        NormalizeLooseText(ws.Cells(rowNumber, K_COL_UNIT).Value2) & "|" & _
        CanonNumber(ws.Cells(rowNumber, K_COL_QTY).Value2, QTY_DECIMALS) & "|" & _
        CanonNumber(ws.Cells(rowNumber, K_COL_PRICE).Value2, PRICE_DECIMALS) & "|" & _
        NormalizeRate(ws.Cells(rowNumber, K_COL_RATE).Value2)
End Function

Private Function DetailLineSignature(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    DetailLineSignature = _
        NormalizeLooseText(ws.Cells(rowNumber, D_COL_ITEM).Value2) & "|" & _
        NormalizeLooseText(ws.Cells(rowNumber, D_COL_UNIT).Value2) & "|" & _
        CanonNumber(ws.Cells(rowNumber, D_COL_QTY).Value2, QTY_DECIMALS) & "|" & _
        CanonNumber(ws.Cells(rowNumber, D_COL_PRICE).Value2, PRICE_DECIMALS) & "|" & _
        NormalizeRate(ws.Cells(rowNumber, D_COL_RATE).Value2)
End Function

Private Function CanonNumber(ByVal value As Variant, ByVal decimals As Long) As String
    Dim roundedValue As Double

    If IsSourceMissing(value) Then
        CanonNumber = "<BLANK>"
    ElseIf Not IsNumeric(value) Then
        CanonNumber = "<TEXT>" & NormalizeLooseText(value)
    Else
        roundedValue = Application.WorksheetFunction.Round(CDbl(value), decimals)
        If decimals = 2 Then
            CanonNumber = Format$(roundedValue, "0.00")
        Else
            CanonNumber = Format$(roundedValue, "0")
        End If
    End If
End Function

Private Function NormalizeLooseText(ByVal value As Variant) As String
    Dim s As String

    If IsSourceMissing(value) Then Exit Function
    s = UCase$(Trim$(SafeText(value)))
    s = Replace(s, ChrW(160), " ")
    Do While InStr(1, s, "  ", vbBinaryCompare) > 0
        s = Replace(s, "  ", " ")
    Loop
    NormalizeLooseText = s
End Function

' Tao phan cong dong chi tiet cho tung dong tong hop goc.
' Neu mot hoa don co nhieu dong tong hop, phan theo thue suat.
Private Function BuildAssignmentsForGroup( _
    ByVal wsK As Worksheet, _
    ByVal khaiRows As Collection, _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection, _
    ByVal globalAssignments As Object) As Boolean

    Dim localAssignments As Object, rateToKhaiRow As Object
    Dim i As Long, khaiRow As Long, detailRow As Long
    Dim firstKhaiRow As Long, targetKhaiRow As Long
    Dim rateKey As String, localKey As Variant
    Dim targetRows As Collection

    Set localAssignments = NewTextDictionary()
    firstKhaiRow = CLng(khaiRows(1))

    For i = 1 To khaiRows.Count
        khaiRow = CLng(khaiRows(i))
        Set targetRows = New Collection
        localAssignments.Add CStr(khaiRow), targetRows
    Next i

    If khaiRows.Count = 1 Then
        Set targetRows = localAssignments(CStr(firstKhaiRow))
        For i = 1 To detailRows.Count
            targetRows.Add CLng(detailRows(i))
        Next i
    Else
        Set rateToKhaiRow = NewTextDictionary()

        For i = 1 To khaiRows.Count
            khaiRow = CLng(khaiRows(i))
            rateKey = NormalizeRate(wsK.Cells(khaiRow, K_COL_RATE).Value2)

            ' Hai dong tong hop co cung thue suat thi khong du can cu de chia chi tiet.
            If rateToKhaiRow.Exists(rateKey) Then Exit Function
            rateToKhaiRow.Add rateKey, khaiRow
        Next i

        For i = 1 To detailRows.Count
            detailRow = CLng(detailRows(i))
            rateKey = NormalizeRate(wsD.Cells(detailRow, D_COL_RATE).Value2)

            If rateToKhaiRow.Exists(rateKey) Then
                targetKhaiRow = CLng(rateToKhaiRow(rateKey))
            Else
                ' Chi cho phep gan vao dong dau neu day la dong KCT/trong va
                ' ca tien hang/tien thue deu bang 0. Truong hop khac la mo ho.
                If Not IsZeroUnratedDetailLine(wsD, detailRow) Then Exit Function
                targetKhaiRow = firstKhaiRow
            End If

            Set targetRows = localAssignments(CStr(targetKhaiRow))
            targetRows.Add detailRow
        Next i

    End If

    ' Chi ghi ke hoach neu moi dong goc deu nhan duoc chi tiet va tong M/O co the bao toan.
    For Each localKey In localAssignments.Keys
        Set targetRows = localAssignments(CStr(localKey))
        If targetRows.Count = 0 Then Exit Function
        ' Voi nhom 1-nhieu, M/O goc bat buoc phai co de co moc phan bo va
        ' bao toan tong. Nhom thieu M/O duoc bo qua de tranh tu sinh so tien.
        If IsBlankValue(wsK.Cells(CLng(localKey), K_COL_AMOUNT).Value2) Then Exit Function
        If IsBlankValue(wsK.Cells(CLng(localKey), K_COL_VAT).Value2) Then Exit Function
        If Not AssignmentReconciles( _
                wsK, CLng(localKey), wsD, targetRows) Then Exit Function
    Next localKey

    For Each localKey In localAssignments.Keys
        Set targetRows = localAssignments(CStr(localKey))
        globalAssignments.Add CStr(localKey), targetRows
    Next localKey

    BuildAssignmentsForGroup = True
End Function

Private Function IsZeroUnratedDetailLine( _
    ByVal wsD As Worksheet, _
    ByVal detailRow As Long) As Boolean

    Dim rateKey As String
    Dim valueN As Variant, valueP As Variant, valueVat As Variant

    rateKey = UCase$(NormalizeRate(wsD.Cells(detailRow, D_COL_RATE).Value2))
    If Len(rateKey) > 0 And rateKey <> "KCT" Then Exit Function

    valueN = wsD.Cells(detailRow, D_COL_TOTAL_GOODS).Value2
    valueP = wsD.Cells(detailRow, D_COL_PRETAX).Value2
    valueVat = wsD.Cells(detailRow, D_COL_VAT).Value2

    If Not IsSourceMissing(valueN) Then
        If Abs(SafeDouble(valueN)) > 0.0000001 Then Exit Function
    End If
    If Not IsSourceMissing(valueP) Then
        If Abs(SafeDouble(valueP)) > 0.0000001 Then Exit Function
    End If
    If Not IsSourceMissing(valueVat) Then
        If Abs(SafeDouble(valueVat)) > 0.0000001 Then Exit Function
    End If

    IsZeroUnratedDetailLine = True
End Function

Private Function AssignmentReconciles( _
    ByVal wsK As Worksheet, _
    ByVal khaiRow As Long, _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection) As Boolean

    Dim i As Long, detailRow As Long
    Dim targetAmount As Double, targetVat As Double
    Dim targetAmountPresent As Boolean, targetVatPresent As Boolean
    Dim amountSourceCol As Long, selectedAmountSum As Double
    Dim detailVatSum As Double, tolerance As Double
    Dim vatValue As Variant

    targetAmountPresent = Not IsBlankValue(wsK.Cells(khaiRow, K_COL_AMOUNT).Value2)
    targetVatPresent = Not IsBlankValue(wsK.Cells(khaiRow, K_COL_VAT).Value2)
    targetAmount = SafeDouble(wsK.Cells(khaiRow, K_COL_AMOUNT).Value2)
    targetVat = SafeDouble(wsK.Cells(khaiRow, K_COL_VAT).Value2)

    amountSourceCol = SelectAmountSourceCol( _
        wsD, detailRows, targetAmount, targetAmountPresent, selectedAmountSum)
    If amountSourceCol = 0 Then Exit Function

    For i = 1 To detailRows.Count
        detailRow = CLng(detailRows(i))
        vatValue = wsD.Cells(detailRow, D_COL_VAT).Value2
        If Not IsSourceMissing(vatValue) Then
            detailVatSum = detailVatSum + SafeDouble(vatValue)
        End If
    Next i

    tolerance = RoundingTolerance(detailRows.Count)
    If targetAmountPresent Then
        If Abs(targetAmount - selectedAmountSum) > tolerance Then Exit Function
    End If
    If targetVatPresent Then
        If Abs(targetVat - detailVatSum) > tolerance Then Exit Function
    End If

    AssignmentReconciles = True
End Function

' Bung mot dong tong hop thanh cac dong chi tiet, giu A:H va P:Q cua dong goc.
Private Sub ExpandOneKhaiRow( _
    ByVal wsK As Worksheet, _
    ByVal wsD As Worksheet, _
    ByVal anchorRow As Long, _
    ByVal detailRows As Collection, _
    ByVal summaryItem As Variant)

    Dim n As Long, i As Long, outRow As Long, detailRow As Long
    Dim baseValues As Variant
    Dim targetAmount As Double, targetVat As Double
    Dim targetAmountPresent As Boolean, targetVatPresent As Boolean
    Dim amountSourceCol As Long
    Dim selectedAmountSum As Double
    Dim rawAmounts() As Double, rawVat() As Double
    Dim amounts() As Double, vatAmounts() As Double
    Dim sourceValue As Variant

    n = detailRows.Count
    If n = 0 Then Exit Sub

    baseValues = wsK.Range(wsK.Cells(anchorRow, 1), wsK.Cells(anchorRow, 17)).Value2
    targetAmountPresent = Not IsBlankValue(baseValues(1, K_COL_AMOUNT))
    targetVatPresent = Not IsBlankValue(baseValues(1, K_COL_VAT))
    targetAmount = SafeDouble(baseValues(1, K_COL_AMOUNT))
    targetVat = SafeDouble(baseValues(1, K_COL_VAT))

    amountSourceCol = SelectAmountSourceCol( _
        wsD, detailRows, targetAmount, targetAmountPresent, selectedAmountSum)
    If amountSourceCol = 0 Then Exit Sub

    ReDim rawAmounts(1 To n)
    ReDim rawVat(1 To n)

    For i = 1 To n
        detailRow = CLng(detailRows(i))
        sourceValue = wsD.Cells(detailRow, amountSourceCol).Value2
        If Not IsSourceMissing(sourceValue) Then rawAmounts(i) = SafeDouble(sourceValue)

        sourceValue = wsD.Cells(detailRow, D_COL_VAT).Value2
        If Not IsSourceMissing(sourceValue) Then rawVat(i) = SafeDouble(sourceValue)
    Next i

    AllocateRounded rawAmounts, n, targetAmount, targetAmountPresent, amounts
    AllocateRounded rawVat, n, targetVat, targetVatPresent, vatAmounts

    ' Chen mot khoi dong, sau do sao chep dinh dang/du lieu nen tu dong goc.
    If n > 1 Then
        wsK.rows(CStr(anchorRow + 1) & ":" & CStr(anchorRow + n - 1)).Insert _
            Shift:=xlDown, CopyOrigin:=xlFormatFromLeftOrAbove
    End If

    For i = 2 To n
        outRow = anchorRow + i - 1
        wsK.Range(wsK.Cells(anchorRow, 1), wsK.Cells(anchorRow, 17)).Copy _
            destination:=wsK.Range(wsK.Cells(outRow, 1), wsK.Cells(outRow, 17))
        wsK.rows(outRow).RowHeight = wsK.rows(anchorRow).RowHeight
    Next i

    For i = 1 To n
        outRow = anchorRow + i - 1
        detailRow = CLng(detailRows(i))

        sourceValue = wsD.Cells(detailRow, D_COL_ITEM).Value2
        If IsSourceMissing(sourceValue) Then sourceValue = summaryItem
        WritePreferredDescription wsK.Cells(outRow, K_COL_ITEM), sourceValue
        WriteDetailField wsK.Cells(outRow, K_COL_UNIT), _
            wsD.Cells(detailRow, D_COL_UNIT).Value2, (i = 1)
        WriteDetailField wsK.Cells(outRow, K_COL_QTY), _
            wsD.Cells(detailRow, D_COL_QTY).Value2, (i = 1)
        WriteDetailField wsK.Cells(outRow, K_COL_PRICE), _
            wsD.Cells(detailRow, D_COL_PRICE).Value2, (i = 1)

        wsK.Cells(outRow, K_COL_AMOUNT).Value2 = amounts(i)

        If IsSourceMissing(wsD.Cells(detailRow, D_COL_RATE).Value2) Then
            If i > 1 Then wsK.Cells(outRow, K_COL_RATE).ClearContents
        Else
            wsK.Cells(outRow, K_COL_RATE).Value2 = _
                RateValueForOutput(wsD.Cells(detailRow, D_COL_RATE).Value2)
        End If

        wsK.Cells(outRow, K_COL_VAT).Value2 = vatAmounts(i)
    Next i
End Sub

Private Function SelectAmountSourceCol( _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection, _
    ByVal targetAmount As Double, _
    ByVal targetPresent As Boolean, _
    ByRef selectedSum As Double) As Long

    Dim i As Long, detailRow As Long
    Dim sumN As Double, sumP As Double
    Dim hasN As Boolean, hasP As Boolean
    Dim valueN As Variant, valueP As Variant

    For i = 1 To detailRows.Count
        detailRow = CLng(detailRows(i))
        valueN = wsD.Cells(detailRow, D_COL_TOTAL_GOODS).Value2
        valueP = wsD.Cells(detailRow, D_COL_PRETAX).Value2

        If Not IsSourceMissing(valueN) Then
            hasN = True
            sumN = sumN + SafeDouble(valueN)
        End If
        If Not IsSourceMissing(valueP) Then
            hasP = True
            sumP = sumP + SafeDouble(valueP)
        End If
    Next i

    If hasN And hasP Then
        If targetPresent Then
            If Abs(targetAmount - sumN) <= Abs(targetAmount - sumP) Then
                SelectAmountSourceCol = D_COL_TOTAL_GOODS
                selectedSum = sumN
            Else
                SelectAmountSourceCol = D_COL_PRETAX
                selectedSum = sumP
            End If
        Else
            SelectAmountSourceCol = D_COL_PRETAX
            selectedSum = sumP
        End If
    ElseIf hasN Then
        SelectAmountSourceCol = D_COL_TOTAL_GOODS
        selectedSum = sumN
    ElseIf hasP Then
        SelectAmountSourceCol = D_COL_PRETAX
        selectedSum = sumP
    End If
End Function

Private Function RoundingTolerance(ByVal valueCount As Long) As Double
    RoundingTolerance = valueCount * 0.6
    If RoundingTolerance < 2# Then RoundingTolerance = 2#
End Function

' Dien cac o I:O con trong khi hoa don chi co dung mot dong chi tiet.
Private Function FillSingleDetailBlanks( _
    ByVal wsK As Worksheet, _
    ByVal wsD As Worksheet, _
    ByVal khaiRow As Long, _
    ByVal detailRow As Long, _
    ByVal summaryItem As Variant) As Long

    Dim sourceValues(1 To 7) As Variant
    Dim destinationCols As Variant
    Dim i As Long, destinationCol As Long
    Dim targetAmountPresent As Boolean, targetAmount As Double
    Dim amountN As Double, amountP As Double
    Dim rawN As Variant, rawP As Variant, rawVat As Variant
    Dim hasN As Boolean, hasP As Boolean

    targetAmountPresent = Not IsBlankValue(wsK.Cells(khaiRow, K_COL_AMOUNT).Value2)
    targetAmount = SafeDouble(wsK.Cells(khaiRow, K_COL_AMOUNT).Value2)
    rawN = wsD.Cells(detailRow, D_COL_TOTAL_GOODS).Value2
    rawP = wsD.Cells(detailRow, D_COL_PRETAX).Value2
    rawVat = wsD.Cells(detailRow, D_COL_VAT).Value2
    hasN = Not IsSourceMissing(rawN)
    hasP = Not IsSourceMissing(rawP)
    amountN = SafeDouble(rawN)
    amountP = SafeDouble(rawP)

    sourceValues(1) = wsD.Cells(detailRow, D_COL_ITEM).Value2
    If IsSourceMissing(sourceValues(1)) Then sourceValues(1) = summaryItem
    sourceValues(2) = wsD.Cells(detailRow, D_COL_UNIT).Value2
    sourceValues(3) = wsD.Cells(detailRow, D_COL_QTY).Value2
    sourceValues(4) = wsD.Cells(detailRow, D_COL_PRICE).Value2

    If hasN And hasP And targetAmountPresent And _
       Abs(targetAmount - amountN) <= Abs(targetAmount - amountP) Then
        sourceValues(5) = ExcelRound(amountN)
    ElseIf hasP Then
        sourceValues(5) = ExcelRound(amountP)
    ElseIf hasN Then
        sourceValues(5) = ExcelRound(amountN)
    End If

    sourceValues(6) = RateValueForOutput(wsD.Cells(detailRow, D_COL_RATE).Value2)
    If Not IsSourceMissing(rawVat) Then
        sourceValues(7) = ExcelRound(SafeDouble(rawVat))
    End If

    ' Cot mo ta la ngoai le duy nhat: duoc thay noi dung goc theo thu tu uu tien.
    If Not IsSourceMissing(sourceValues(1)) Then
        If Not wsK.Cells(khaiRow, K_COL_ITEM).HasFormula Then
            If StrComp(NormalizeLooseText(wsK.Cells(khaiRow, K_COL_ITEM).Value2), _
                       NormalizeLooseText(sourceValues(1)), vbTextCompare) <> 0 Then
                wsK.Cells(khaiRow, K_COL_ITEM).Value2 = sourceValues(1)
                FillSingleDetailBlanks = FillSingleDetailBlanks + 1
            End If
        End If
    End If

    destinationCols = Array( _
        K_COL_ITEM, K_COL_UNIT, K_COL_QTY, K_COL_PRICE, _
        K_COL_AMOUNT, K_COL_RATE, K_COL_VAT)

    For i = 2 To 7
        destinationCol = CLng(destinationCols(i - 1))
        If IsBlankValue(wsK.Cells(khaiRow, destinationCol).Value2) And _
           Not wsK.Cells(khaiRow, destinationCol).HasFormula Then
            If Not IsSourceMissing(sourceValues(i)) Then
                wsK.Cells(khaiRow, destinationCol).Value2 = sourceValues(i)
                FillSingleDetailBlanks = FillSingleDetailBlanks + 1
            End If
        End If
    Next i
End Function

Private Sub WritePreferredDescription( _
    ByVal destination As Range, _
    ByVal sourceValue As Variant)

    If IsSourceMissing(sourceValue) Then Exit Sub
    If destination.HasFormula Then Exit Sub

    If StrComp(NormalizeLooseText(destination.Value2), _
               NormalizeLooseText(sourceValue), vbTextCompare) <> 0 Then
        destination.Value2 = sourceValue
    End If
End Sub

Private Sub WriteDetailField( _
    ByVal destination As Range, _
    ByVal sourceValue As Variant, _
    ByVal keepExistingWhenMissing As Boolean)

    If IsSourceMissing(sourceValue) Then
        If Not keepExistingWhenMissing Then destination.ClearContents
    Else
        destination.Value2 = sourceValue
    End If
End Sub

' Lam tron tung dong, roi dieu chinh dong co gia tri cuoi cung de tong bang dong goc.
Private Sub AllocateRounded( _
    ByRef rawValues() As Double, _
    ByVal valueCount As Long, _
    ByVal targetValue As Double, _
    ByVal targetPresent As Boolean, _
    ByRef result() As Double)

    Dim i As Long, lastNonZero As Long
    Dim rawSum As Double, roundedSum As Double
    Dim tolerance As Double, delta As Double

    ReDim result(1 To valueCount)

    For i = 1 To valueCount
        rawSum = rawSum + rawValues(i)
        result(i) = ExcelRound(rawValues(i))
        roundedSum = roundedSum + result(i)
        If Abs(rawValues(i)) > 0.0000001 Then lastNonZero = i
    Next i

    If Not targetPresent Then Exit Sub

    tolerance = RoundingTolerance(valueCount)
    If Abs(targetValue - rawSum) > tolerance Then Exit Sub

    delta = ExcelRound(targetValue) - roundedSum
    If Abs(delta) < 0.0000001 Then Exit Sub
    If lastNonZero = 0 Then lastNonZero = valueCount

    result(lastNonZero) = result(lastNonZero) + delta
End Sub

Private Function GroupsReconcile( _
    ByVal wsK As Worksheet, _
    ByVal khaiRows As Collection, _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection) As Boolean

    Dim i As Long, rowNumber As Long
    Dim khaiAmount As Double, khaiVat As Double
    Dim detailN As Double, detailP As Double, detailVat As Double
    Dim hasKhaiAmount As Boolean, hasKhaiVat As Boolean
    Dim sourceValue As Variant

    For i = 1 To khaiRows.Count
        rowNumber = CLng(khaiRows(i))
        If Not IsBlankValue(wsK.Cells(rowNumber, K_COL_AMOUNT).Value2) Then
            hasKhaiAmount = True
            khaiAmount = khaiAmount + SafeDouble(wsK.Cells(rowNumber, K_COL_AMOUNT).Value2)
        End If
        If Not IsBlankValue(wsK.Cells(rowNumber, K_COL_VAT).Value2) Then
            hasKhaiVat = True
            khaiVat = khaiVat + SafeDouble(wsK.Cells(rowNumber, K_COL_VAT).Value2)
        End If
    Next i

    If Not hasKhaiAmount Or Not hasKhaiVat Then Exit Function

    For i = 1 To detailRows.Count
        rowNumber = CLng(detailRows(i))
        sourceValue = wsD.Cells(rowNumber, D_COL_TOTAL_GOODS).Value2
        If Not IsSourceMissing(sourceValue) Then detailN = detailN + SafeDouble(sourceValue)

        sourceValue = wsD.Cells(rowNumber, D_COL_PRETAX).Value2
        If Not IsSourceMissing(sourceValue) Then detailP = detailP + SafeDouble(sourceValue)

        sourceValue = wsD.Cells(rowNumber, D_COL_VAT).Value2
        If Not IsSourceMissing(sourceValue) Then detailVat = detailVat + SafeDouble(sourceValue)
    Next i

    GroupsReconcile = _
        ((Abs(khaiAmount - detailN) <= AMOUNT_TOLERANCE) Or _
         (Abs(khaiAmount - detailP) <= AMOUNT_TOLERANCE)) And _
        (Abs(khaiVat - detailVat) <= VAT_TOLERANCE)
End Function

' Xac nhan nhom chi tiet voi dung mot dong tren sheet tong hop hoa don.
' Sheet tong hop dung de doi chieu khoa/tong va cap dien giai uu tien 2;
' khong cap don vi tinh, so luong hay don gia.
Private Function DetailGroupConfirmedBySummary( _
    ByVal wsD As Worksheet, _
    ByVal detailRows As Collection, _
    ByVal wsS As Worksheet, _
    ByVal summaryGroups As Object, _
    ByVal detailKey As String) As Boolean

    Dim summaryRows As Collection
    Dim i As Long, rowNumber As Long, summaryRow As Long
    Dim detailN As Double, detailP As Double, detailVat As Double
    Dim summaryN As Double, summaryP As Double, summaryVat As Double
    Dim hasDetailN As Boolean, hasDetailP As Boolean, hasDetailVat As Boolean
    Dim hasSummaryN As Boolean, hasSummaryP As Boolean, hasSummaryVat As Boolean
    Dim sourceValue As Variant, amountMatches As Boolean

    If Not summaryGroups.Exists(detailKey) Then Exit Function
    Set summaryRows = summaryGroups(detailKey)
    If summaryRows.Count <> 1 Then Exit Function

    For i = 1 To detailRows.Count
        rowNumber = CLng(detailRows(i))
        sourceValue = wsD.Cells(rowNumber, D_COL_TOTAL_GOODS).Value2
        If Not IsSourceMissing(sourceValue) Then
            hasDetailN = True
            detailN = detailN + SafeDouble(sourceValue)
        End If
        sourceValue = wsD.Cells(rowNumber, D_COL_PRETAX).Value2
        If Not IsSourceMissing(sourceValue) Then
            hasDetailP = True
            detailP = detailP + SafeDouble(sourceValue)
        End If
        sourceValue = wsD.Cells(rowNumber, D_COL_VAT).Value2
        If Not IsSourceMissing(sourceValue) Then
            hasDetailVat = True
            detailVat = detailVat + SafeDouble(sourceValue)
        End If
    Next i

    summaryRow = CLng(summaryRows(1))
    sourceValue = wsS.Cells(summaryRow, S_COL_TOTAL_GOODS).Value2
    If Not IsSourceMissing(sourceValue) Then
        hasSummaryN = True
        summaryN = SafeDouble(sourceValue)
    End If
    sourceValue = wsS.Cells(summaryRow, S_COL_PRETAX).Value2
    If Not IsSourceMissing(sourceValue) Then
        hasSummaryP = True
        summaryP = SafeDouble(sourceValue)
    End If
    sourceValue = wsS.Cells(summaryRow, S_COL_VAT).Value2
    If Not IsSourceMissing(sourceValue) Then
        hasSummaryVat = True
        summaryVat = SafeDouble(sourceValue)
    End If

    If hasDetailN And hasSummaryN Then _
        amountMatches = (Abs(detailN - summaryN) <= AMOUNT_TOLERANCE)
    If Not amountMatches And hasDetailN And hasSummaryP Then _
        amountMatches = (Abs(detailN - summaryP) <= AMOUNT_TOLERANCE)
    If Not amountMatches And hasDetailP And hasSummaryN Then _
        amountMatches = (Abs(detailP - summaryN) <= AMOUNT_TOLERANCE)
    If Not amountMatches And hasDetailP And hasSummaryP Then _
        amountMatches = (Abs(detailP - summaryP) <= AMOUNT_TOLERANCE)

    DetailGroupConfirmedBySummary = amountMatches And hasDetailVat And _
        hasSummaryVat And (Abs(detailVat - summaryVat) <= VAT_TOLERANCE)
End Function

Private Function KhaiFullKey(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    ' Khoa exact khong dung Mau so vi cot nay co the trong tren KHAI THUE.
    ' Ky hieu + So HD + Ngay + MST da du de nhan dien hoa don exact.
    KhaiFullKey = _
        CompactKeyPart(ws.Cells(rowNumber, K_COL_SERIES).Value2) & "|" & _
        NormalizeInvoiceNumber(ws.Cells(rowNumber, K_COL_NUMBER).Value2) & "|" & _
        NormalizeDateKey(ws.Cells(rowNumber, K_COL_DATE).Value2) & "|" & _
        NormalizeTaxId(ws.Cells(rowNumber, K_COL_TAX_ID).Value2)
End Function

Private Function DetailFullKey(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    DetailFullKey = _
        CompactKeyPart(ws.Cells(rowNumber, D_COL_SERIES).Value2) & "|" & _
        NormalizeInvoiceNumber(ws.Cells(rowNumber, D_COL_NUMBER).Value2) & "|" & _
        NormalizeDateKey(ws.Cells(rowNumber, D_COL_DATE).Value2) & "|" & _
        NormalizeTaxId(ws.Cells(rowNumber, D_COL_TAX_ID).Value2)
End Function

Private Function SummaryFullKey(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    SummaryFullKey = _
        CompactKeyPart(ws.Cells(rowNumber, S_COL_SERIES).Value2) & "|" & _
        NormalizeInvoiceNumber(ws.Cells(rowNumber, S_COL_NUMBER).Value2) & "|" & _
        NormalizeDateKey(ws.Cells(rowNumber, S_COL_DATE).Value2) & "|" & _
        NormalizeTaxId(ws.Cells(rowNumber, S_COL_TAX_ID).Value2)
End Function

Private Function CanUseKhaiFull(ByVal ws As Worksheet, ByVal rowNumber As Long) As Boolean
    CanUseKhaiFull = _
        (Len(CompactKeyPart(ws.Cells(rowNumber, K_COL_SERIES).Value2)) > 0) And _
        (Len(NormalizeInvoiceNumber(ws.Cells(rowNumber, K_COL_NUMBER).Value2)) > 0) And _
        (Len(NormalizeDateKey(ws.Cells(rowNumber, K_COL_DATE).Value2)) > 0) And _
        (Len(NormalizeTaxId(ws.Cells(rowNumber, K_COL_TAX_ID).Value2)) > 0)
End Function

Private Function CanUseDetailFull(ByVal ws As Worksheet, ByVal rowNumber As Long) As Boolean
    CanUseDetailFull = _
        (Len(CompactKeyPart(ws.Cells(rowNumber, D_COL_SERIES).Value2)) > 0) And _
        (Len(NormalizeInvoiceNumber(ws.Cells(rowNumber, D_COL_NUMBER).Value2)) > 0) And _
        (Len(NormalizeDateKey(ws.Cells(rowNumber, D_COL_DATE).Value2)) > 0) And _
        (Len(NormalizeTaxId(ws.Cells(rowNumber, D_COL_TAX_ID).Value2)) > 0)
End Function

Private Function CanUseSummaryFull(ByVal ws As Worksheet, ByVal rowNumber As Long) As Boolean
    CanUseSummaryFull = _
        (Len(CompactKeyPart(ws.Cells(rowNumber, S_COL_SERIES).Value2)) > 0) And _
        (Len(NormalizeInvoiceNumber(ws.Cells(rowNumber, S_COL_NUMBER).Value2)) > 0) And _
        (Len(NormalizeDateKey(ws.Cells(rowNumber, S_COL_DATE).Value2)) > 0) And _
        (Len(NormalizeTaxId(ws.Cells(rowNumber, S_COL_TAX_ID).Value2)) > 0)
End Function

' Khoa core bo qua ngay, chi dung khi ung vien chi tiet duy nhat va tong M/O khop.
Private Function KhaiCoreKey(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    KhaiCoreKey = _
        CompactKeyPart(ws.Cells(rowNumber, K_COL_MODEL).Value2) & "|" & _
        CompactKeyPart(ws.Cells(rowNumber, K_COL_SERIES).Value2) & "|" & _
        NormalizeInvoiceNumber(ws.Cells(rowNumber, K_COL_NUMBER).Value2) & "|" & _
        NormalizeTaxId(ws.Cells(rowNumber, K_COL_TAX_ID).Value2)
End Function

Private Function DetailCoreKey(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    DetailCoreKey = _
        CompactKeyPart(ws.Cells(rowNumber, D_COL_MODEL).Value2) & "|" & _
        CompactKeyPart(ws.Cells(rowNumber, D_COL_SERIES).Value2) & "|" & _
        NormalizeInvoiceNumber(ws.Cells(rowNumber, D_COL_NUMBER).Value2) & "|" & _
        NormalizeTaxId(ws.Cells(rowNumber, D_COL_TAX_ID).Value2)
End Function

Private Function CanUseKhaiCore(ByVal ws As Worksheet, ByVal rowNumber As Long) As Boolean
    CanUseKhaiCore = _
        (Len(CompactKeyPart(ws.Cells(rowNumber, K_COL_MODEL).Value2)) > 0) And _
        (Len(CompactKeyPart(ws.Cells(rowNumber, K_COL_SERIES).Value2)) > 0) And _
        (Len(NormalizeInvoiceNumber(ws.Cells(rowNumber, K_COL_NUMBER).Value2)) > 0) And _
        (Len(NormalizeTaxId(ws.Cells(rowNumber, K_COL_TAX_ID).Value2)) > 0)
End Function

Private Function CanUseDetailCore(ByVal ws As Worksheet, ByVal rowNumber As Long) As Boolean
    CanUseDetailCore = _
        (Len(CompactKeyPart(ws.Cells(rowNumber, D_COL_MODEL).Value2)) > 0) And _
        (Len(CompactKeyPart(ws.Cells(rowNumber, D_COL_SERIES).Value2)) > 0) And _
        (Len(NormalizeInvoiceNumber(ws.Cells(rowNumber, D_COL_NUMBER).Value2)) > 0) And _
        (Len(NormalizeTaxId(ws.Cells(rowNumber, D_COL_TAX_ID).Value2)) > 0)
End Function

' Khoa fallback co y bo qua Ky hieu, nhung van giu Mau so + So HD + Ngay + MST.
Private Function KhaiFallbackKey(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    KhaiFallbackKey = _
        CompactKeyPart(ws.Cells(rowNumber, K_COL_MODEL).Value2) & "|" & _
        NormalizeInvoiceNumber(ws.Cells(rowNumber, K_COL_NUMBER).Value2) & "|" & _
        NormalizeDateKey(ws.Cells(rowNumber, K_COL_DATE).Value2) & "|" & _
        NormalizeTaxId(ws.Cells(rowNumber, K_COL_TAX_ID).Value2)
End Function

Private Function DetailFallbackKey(ByVal ws As Worksheet, ByVal rowNumber As Long) As String
    DetailFallbackKey = _
        CompactKeyPart(ws.Cells(rowNumber, D_COL_MODEL).Value2) & "|" & _
        NormalizeInvoiceNumber(ws.Cells(rowNumber, D_COL_NUMBER).Value2) & "|" & _
        NormalizeDateKey(ws.Cells(rowNumber, D_COL_DATE).Value2) & "|" & _
        NormalizeTaxId(ws.Cells(rowNumber, D_COL_TAX_ID).Value2)
End Function

' Khi workbook co nhieu nam, fallback bo qua ngay van phai cung nam neu ca hai
' ben deu co ngay hop le. Quy tac nay ngan ghep nham so hoa don lap lai qua nam.
Private Function DatesCompatibleForFallback( _
    ByVal wsK As Worksheet, _
    ByVal khaiRow As Long, _
    ByVal wsD As Worksheet, _
    ByVal detailRow As Long) As Boolean

    Dim khaiDate As String, detailDate As String

    khaiDate = NormalizeDateKey(wsK.Cells(khaiRow, K_COL_DATE).Value2)
    detailDate = NormalizeDateKey(wsD.Cells(detailRow, D_COL_DATE).Value2)

    If Len(khaiDate) = 0 Or Len(detailDate) = 0 Then
        DatesCompatibleForFallback = True
    Else
        DatesCompatibleForFallback = _
            (Left$(khaiDate, 4) = Left$(detailDate, 4))
    End If
End Function

Private Function CanUseKhaiFallback(ByVal ws As Worksheet, ByVal rowNumber As Long) As Boolean
    CanUseKhaiFallback = _
        (Len(CompactKeyPart(ws.Cells(rowNumber, K_COL_MODEL).Value2)) > 0) And _
        (Len(NormalizeInvoiceNumber(ws.Cells(rowNumber, K_COL_NUMBER).Value2)) > 0) And _
        (Len(NormalizeDateKey(ws.Cells(rowNumber, K_COL_DATE).Value2)) > 0) And _
        (Len(NormalizeTaxId(ws.Cells(rowNumber, K_COL_TAX_ID).Value2)) > 0)
End Function

Private Function CanUseDetailFallback(ByVal ws As Worksheet, ByVal rowNumber As Long) As Boolean
    CanUseDetailFallback = _
        (Len(CompactKeyPart(ws.Cells(rowNumber, D_COL_MODEL).Value2)) > 0) And _
        (Len(NormalizeInvoiceNumber(ws.Cells(rowNumber, D_COL_NUMBER).Value2)) > 0) And _
        (Len(NormalizeDateKey(ws.Cells(rowNumber, D_COL_DATE).Value2)) > 0) And _
        (Len(NormalizeTaxId(ws.Cells(rowNumber, D_COL_TAX_ID).Value2)) > 0)
End Function

Private Function NormalizeInvoiceNumber(ByVal value As Variant) As String
    Dim s As String

    If IsError(value) Or IsEmpty(value) Then Exit Function

    If IsNumeric(value) Then
        If CDbl(value) = Fix(CDbl(value)) Then
            s = Format$(CDbl(value), "0")
        Else
            s = SafeText(value)
        End If
    Else
        s = Trim$(SafeText(value))
    End If

    If IsDigitsOnly(s) Then
        Do While Len(s) > 1 And Left$(s, 1) = "0"
            s = Mid$(s, 2)
        Loop
        NormalizeInvoiceNumber = s
    Else
        NormalizeInvoiceNumber = CompactKeyPart(s)
    End If
End Function

Private Function NormalizeTaxId(ByVal value As Variant) As String
    Dim s As String

    s = CompactKeyPart(value)
    If IsDigitsOnly(s) Then
        Do While Len(s) > 1 And Left$(s, 1) = "0"
            s = Mid$(s, 2)
        Loop
    End If
    NormalizeTaxId = s
End Function

Private Function NormalizeDateKey(ByVal value As Variant) As String
    Dim serialValue As Double, s As String, resultKey As String
    Dim parts() As String
    Dim yearPart As Long, monthPart As Long, dayPart As Long

    If IsError(value) Or IsEmpty(value) Then Exit Function

    If VarType(value) = vbDate Then
        NormalizeDateKey = Format$(CDate(value), "yyyymmdd")
        Exit Function
    End If

    s = Trim$(SafeText(value))
    If Len(s) = 8 And IsDigitsOnly(s) Then
        yearPart = CLng(Left$(s, 4))
        monthPart = CLng(Mid$(s, 5, 2))
        dayPart = CLng(Right$(s, 2))
        If TryComposeDateKey(yearPart, monthPart, dayPart, resultKey) Then
            NormalizeDateKey = resultKey
            Exit Function
        End If
    End If

    If IsNumeric(value) And InStr(1, SafeText(value), "/", vbBinaryCompare) = 0 And _
       InStr(1, SafeText(value), "-", vbBinaryCompare) = 0 Then
        serialValue = CDbl(value)
        If serialValue >= 1# And serialValue <= 2958465# Then
            NormalizeDateKey = Format$(DateSerial(1899, 12, 30) + CLng(Fix(serialValue)), "yyyymmdd")
            Exit Function
        End If
    End If

    s = Replace(s, "-", "/")
    s = Replace(s, ".", "/")
    parts = Split(s, "/")

    If UBound(parts) = 2 Then
        If IsNumeric(parts(0)) And IsNumeric(parts(1)) And IsNumeric(parts(2)) Then
            If Len(Trim$(parts(0))) = 4 Then
                yearPart = CLng(parts(0))
                monthPart = CLng(parts(1))
                dayPart = CLng(parts(2))
            Else
                dayPart = CLng(parts(0))
                monthPart = CLng(parts(1))
                yearPart = CLng(parts(2))
            End If

            If TryComposeDateKey(yearPart, monthPart, dayPart, resultKey) Then
                NormalizeDateKey = resultKey
                Exit Function
            End If
        End If
    End If

    If IsDate(value) Then
        NormalizeDateKey = Format$(CDate(value), "yyyymmdd")
        Exit Function
    End If

    ' Chuoi ngay khong hop le/khong nhan dien duoc: de trong de buoc fallback
    ' phai dung ung vien duy nhat va doi chieu tong, khong ghep exact mu quang.
    NormalizeDateKey = vbNullString
End Function

Private Function TryComposeDateKey( _
    ByVal yearPart As Long, _
    ByVal monthPart As Long, _
    ByVal dayPart As Long, _
    ByRef resultKey As String) As Boolean

    Dim parsedDate As Date

    On Error GoTo InvalidDate
    parsedDate = DateSerial(yearPart, monthPart, dayPart)
    If Year(parsedDate) <> yearPart Then Exit Function
    If Month(parsedDate) <> monthPart Then Exit Function
    If Day(parsedDate) <> dayPart Then Exit Function

    resultKey = Format$(parsedDate, "yyyymmdd")
    TryComposeDateKey = True
    Exit Function
InvalidDate:
End Function

Private Function NormalizeRate(ByVal value As Variant) As String
    Dim s As String, n As Double

    If IsSourceMissing(value) Then Exit Function

    s = UCase$(Trim$(SafeText(value)))
    s = Replace(s, " ", "")
    s = Replace(s, "%", "")

    If IsNumeric(s) Then
        n = CDbl(s)
        If Abs(n) > 0# And Abs(n) <= 1# Then n = n * 100#
        NormalizeRate = Format$(n, "0.########")
    Else
        NormalizeRate = s
    End If
End Function

' Gia tri ghi ra cot N: thue suat so duoc giu la Number; KCT/khac la Text.
Private Function RateValueForOutput(ByVal value As Variant) As Variant
    Dim rateKey As String

    If IsSourceMissing(value) Then
        RateValueForOutput = Empty
        Exit Function
    End If

    rateKey = NormalizeRate(value)
    If IsNumeric(rateKey) Then
        RateValueForOutput = CDbl(rateKey)
    Else
        RateValueForOutput = rateKey
    End If
End Function

Private Function CompactKeyPart(ByVal value As Variant) As String
    Dim s As String

    s = UCase$(Trim$(SafeText(value)))
    s = Replace(s, ChrW(160), " ")
    Do While InStr(1, s, "  ", vbBinaryCompare) > 0
        s = Replace(s, "  ", " ")
    Loop
    s = Replace(s, " ", "")
    s = Replace(s, ".", "")
    s = Replace(s, "-", "")
    s = Replace(s, "_", "")
    s = Replace(s, "/", "")

    CompactKeyPart = s
End Function

Private Function IsDigitsOnly(ByVal value As String) As Boolean
    Dim i As Long, ch As String

    If Len(value) = 0 Then Exit Function
    For i = 1 To Len(value)
        ch = Mid$(value, i, 1)
        If ch < "0" Or ch > "9" Then Exit Function
    Next i
    IsDigitsOnly = True
End Function

Private Function IsBlankValue(ByVal value As Variant) As Boolean
    If IsError(value) Or IsEmpty(value) Then
        IsBlankValue = True
    Else
        IsBlankValue = (Len(Trim$(SafeText(value))) = 0)
    End If
End Function

' "1916" thuong la o trong/0 bi dinh dang nam trong nguon; khong mang sang KHAI THUE.
Private Function IsSourceMissing(ByVal value As Variant) As Boolean
    Dim s As String

    If IsBlankValue(value) Then
        IsSourceMissing = True
        Exit Function
    End If

    ' Chi coi chuoi placeholder "1916" la thieu; so 1916 co the la gia tri hop le.
    If VarType(value) = vbString Then
        s = UCase$(Trim$(CStr(value)))
        IsSourceMissing = (s = "1916")
    End If
End Function

Private Function SafeDouble(ByVal value As Variant) As Double
    Dim s As String

    If IsError(value) Or IsEmpty(value) Then Exit Function
    If IsNumeric(value) Then
        SafeDouble = CDbl(value)
        Exit Function
    End If

    s = Trim$(SafeText(value))
    s = Replace(s, ",", "")
    s = Replace(s, "%", "")
    If IsNumeric(s) Then SafeDouble = CDbl(s)
End Function

Private Function ExcelRound(ByVal value As Double) As Double
    ExcelRound = Application.WorksheetFunction.Round(value, 0)
End Function

Private Function SafeText(ByVal value As Variant) As String
    If IsError(value) Or IsEmpty(value) Or IsNull(value) Then Exit Function
    SafeText = CStr(value)
End Function

' ================================================================
' TU DONG NHAN DIEN TIEU DE/COT - KHONG PHU THUOC THANG, NAM
' ================================================================
Private Sub DetectAllLayouts( _
    ByVal wsK As Worksheet, _
    ByVal wsD As Worksheet, _
    ByVal wsS As Worksheet)

    DetectKhaiLayout wsK
    DetectDetailLayout wsD
    DetectSummaryLayout wsS
End Sub

Private Sub DetectKhaiLayout( _
    ByVal ws As Worksheet, _
    Optional ByVal requireFullWorkflow As Boolean = True)
    Dim headerBottom As Long, foundRow As Long

    K_COL_MODEL = FindHeaderColumn(ws, Array("MAU SO"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_SERIES = FindHeaderColumn(ws, Array("KY HIEU", "KY HIEU HOA DON"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_NUMBER = FindHeaderColumn(ws, Array("SO HOA DON", "SO HD", "SO"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_DATE = FindHeaderColumn(ws, Array("NGAY HOA DON", "NGAY THANG NAM", "NGAY"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_VENDOR = FindHeaderColumn(ws, Array("TEN NGUOI BAN", "TEN NHA CUNG CAP", "NGUOI BAN"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_TAX_ID = FindHeaderColumn(ws, Array("MA SO THUE NGUOI BAN", "MST NGUOI BAN", "MA SO THUE", "MST"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_ITEM = FindHeaderColumn(ws, Array("TEN HANG HOA DICH VU", "DIEN GIAI", "NOI DUNG"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_UNIT = FindHeaderColumn(ws, Array("DON VI TINH", "DVT"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_QTY = FindHeaderColumn(ws, Array("SO LUONG", "SL"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_PRICE = FindHeaderColumn(ws, Array("DON GIA", "GIA DON VI"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_AMOUNT = FindHeaderColumn(ws, Array( _
        "GIA TRI HHDV MUA VAO CHUA CO THUE GTGT", _
        "GIA TRI HHDV MUA VAO CHUA CO THE GTGT", _
        "GIA TRI HANG HOA DICH VU CHUA THUE", _
        "DOANH SO CHUA THUE", "GIA TRI CHUA THUE", "TIEN TRUOC THUE"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_RATE = FindHeaderColumn(ws, Array("THUE SUAT", "THUE SUAT GTGT"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    K_COL_VAT = FindHeaderColumn(ws, Array("TIEN THUE GTGT", "THUE GTGT", "TIEN THUE"), 20, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)

    ' Du phong cho mau bang ke co dong danh so (2)..(14). Cach nay giup nhan
    ' dien dung cot ngay ca khi tieu de co loi chinh ta, vi du "THE"/"THUE".
    If K_COL_MODEL = 0 Then K_COL_MODEL = FindNumberedFieldColumn(ws, "2", 20, foundRow)
    If K_COL_SERIES = 0 Then K_COL_SERIES = FindNumberedFieldColumn(ws, "3", 20, foundRow)
    If K_COL_NUMBER = 0 Then K_COL_NUMBER = FindNumberedFieldColumn(ws, "4", 20, foundRow)
    If K_COL_DATE = 0 Then K_COL_DATE = FindNumberedFieldColumn(ws, "5", 20, foundRow)
    If K_COL_VENDOR = 0 Then K_COL_VENDOR = FindNumberedFieldColumn(ws, "6", 20, foundRow)
    If K_COL_TAX_ID = 0 Then K_COL_TAX_ID = FindNumberedFieldColumn(ws, "7", 20, foundRow)
    If K_COL_ITEM = 0 Then K_COL_ITEM = FindNumberedFieldColumn(ws, "8", 20, foundRow)
    If K_COL_UNIT = 0 Then K_COL_UNIT = FindNumberedFieldColumn(ws, "9", 20, foundRow)
    If K_COL_QTY = 0 Then K_COL_QTY = FindNumberedFieldColumn(ws, "10", 20, foundRow)
    If K_COL_PRICE = 0 Then K_COL_PRICE = FindNumberedFieldColumn(ws, "11", 20, foundRow)
    If K_COL_AMOUNT = 0 Then K_COL_AMOUNT = FindNumberedFieldColumn(ws, "12", 20, foundRow)
    If K_COL_RATE = 0 Then K_COL_RATE = FindNumberedFieldColumn(ws, "13", 20, foundRow)
    If K_COL_VAT = 0 Then K_COL_VAT = FindNumberedFieldColumn(ws, "14", 20, foundRow)

    RequireColumn K_COL_SERIES, ws.Name, "Ky hieu"
    RequireColumn K_COL_NUMBER, ws.Name, "So hoa don"
    RequireColumn K_COL_DATE, ws.Name, "Ngay hoa don"
    RequireColumn K_COL_TAX_ID, ws.Name, "Ma so thue nguoi ban"
    RequireColumn K_COL_ITEM, ws.Name, "Ten hang hoa/dich vu"
    RequireColumn K_COL_UNIT, ws.Name, "Don vi tinh"
    RequireColumn K_COL_QTY, ws.Name, "So luong"
    RequireColumn K_COL_PRICE, ws.Name, "Don gia"
    If requireFullWorkflow Then
        RequireColumn K_COL_MODEL, ws.Name, "Mau so"
        RequireColumn K_COL_AMOUNT, ws.Name, "Gia tri chua thue"
        RequireColumn K_COL_RATE, ws.Name, "Thue suat"
        RequireColumn K_COL_VAT, ws.Name, "Tien thue GTGT"
    End If
    If K_COL_VENDOR = 0 Then K_COL_VENDOR = K_COL_TAX_ID - 1

    FIRST_KHAI_ROW = FirstDataRow(ws, K_COL_NUMBER, headerBottom + 1)
End Sub

Private Sub DetectDetailLayout( _
    ByVal ws As Worksheet, _
    Optional ByVal requireFullWorkflow As Boolean = True)
    Dim headerBottom As Long, foundRow As Long

    D_COL_MODEL = FindHeaderColumn(ws, Array("MAU SO"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_SERIES = FindHeaderColumn(ws, Array("KY HIEU", "KY HIEU HOA DON"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_NUMBER = FindHeaderColumn(ws, Array("SO HOA DON", "SO HD"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_DATE = FindHeaderColumn(ws, Array("NGAY HOA DON"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_VENDOR = FindHeaderColumn(ws, Array("TEN NGUOI BAN", "NGUOI BAN"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_TAX_ID = FindHeaderColumn(ws, Array("MST NGUOI BAN", "MA SO THUE NGUOI BAN", "MA SO THUE"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    ' Theo mau nguon cua nguoi dung, cot W (23) luon la Dien giai.
    D_COL_ITEM = 23
    foundRow = FindHeaderRowInColumn(ws, D_COL_ITEM, _
        Array("DIEN GIAI", "TEN HANG HOA DICH VU", "NOI DUNG"), 15)
    If foundRow > 0 Then
        headerBottom = MaxLong(headerBottom, foundRow)
    Else
        D_COL_ITEM = 0
    End If
    D_COL_UNIT = FindHeaderColumn(ws, Array("DON VI TINH", "DVT"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_QTY = FindHeaderColumn(ws, Array("SO LUONG", "SL"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_PRICE = FindHeaderColumn(ws, Array("DON GIA", "GIA DON VI"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_TOTAL_GOODS = FindHeaderColumn(ws, Array("TONG TIEN HANG", "TIEN HANG"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_PRETAX = FindHeaderColumn(ws, Array("DOANH SO BAN CHUA THUE", "DOANH SO CHUA THUE", "GIA TRI CHUA THUE"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_RATE = FindHeaderColumn(ws, Array("THUE SUAT", "THUE SUAT GTGT"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    D_COL_VAT = FindHeaderColumn(ws, Array("THUE GTGT", "TIEN THUE GTGT", "TIEN THUE"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)

    RequireColumn D_COL_SERIES, ws.Name, "Ky hieu"
    RequireColumn D_COL_NUMBER, ws.Name, "So hoa don"
    RequireColumn D_COL_DATE, ws.Name, "Ngay hoa don"
    RequireColumn D_COL_TAX_ID, ws.Name, "MST nguoi ban"
    If requireFullWorkflow Then
        RequireColumn D_COL_MODEL, ws.Name, "Mau so"
        RequireColumn D_COL_ITEM, ws.Name, "Dien giai tai cot W"
        RequireColumn D_COL_UNIT, ws.Name, "Don vi tinh"
        RequireColumn D_COL_QTY, ws.Name, "So luong"
        RequireColumn D_COL_PRICE, ws.Name, "Don gia"
        RequireColumn D_COL_PRETAX, ws.Name, "Doanh so chua thue"
        RequireColumn D_COL_RATE, ws.Name, "Thue suat"
        RequireColumn D_COL_VAT, ws.Name, "Thue GTGT"
    End If
    If D_COL_TOTAL_GOODS = 0 Then D_COL_TOTAL_GOODS = D_COL_PRETAX
    If D_COL_VENDOR = 0 Then D_COL_VENDOR = D_COL_TAX_ID - 2

    FIRST_DETAIL_ROW = FirstDataRow(ws, D_COL_NUMBER, headerBottom + 1)
End Sub

Private Sub DetectSummaryLayout( _
    ByVal ws As Worksheet, _
    Optional ByVal requireFullWorkflow As Boolean = True)
    Dim headerBottom As Long, foundRow As Long

    S_COL_MODEL = FindHeaderColumn(ws, Array("MAU SO"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_SERIES = FindHeaderColumn(ws, Array("KY HIEU", "KY HIEU HOA DON"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_NUMBER = FindHeaderColumn(ws, Array("SO HOA DON", "SO HD"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_DATE = FindHeaderColumn(ws, Array("NGAY HOA DON"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_VENDOR = FindHeaderColumn(ws, Array("TEN NGUOI BAN", "NGUOI BAN"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_TAX_ID = FindHeaderColumn(ws, Array("MA SO THUE", "MST NGUOI BAN", "MA SO THUE NGUOI BAN"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    ' Mau bang ke mua vao tong hop: 53 cot A:BA, khong co Thue GTGT quy doi.
    ' W: Dien giai; X: Tong tien hang; Z: Doanh so ban chua thue;
    ' AB: Thue suat; AC: Thue GTGT. Doc theo ten de khong lech khi doi cot.
    ' Ho tro tieu de tai dong 1 trong file nay hoac dong 7 cua file xuat.
    S_COL_ITEM = FindHeaderColumn(ws, _
        Array("DIEN GIAI", "TEN HANG HOA DICH VU", "NOI DUNG"), 15, foundRow)
    headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_UNIT = FindHeaderColumn(ws, Array("DON VI TINH", "DVT"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_QTY = FindHeaderColumn(ws, Array("SO LUONG", "SL"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_PRICE = FindHeaderColumn(ws, Array("DON GIA", "GIA DON VI"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_TOTAL_GOODS = FindHeaderColumn(ws, Array("TONG TIEN HANG", "TIEN HANG"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_PRETAX = FindHeaderColumn(ws, Array("DOANH SO BAN CHUA THUE", "DOANH SO CHUA THUE", "GIA TRI CHUA THUE"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_RATE = FindHeaderColumn(ws, Array("THUE SUAT", "THUE SUAT GTGT"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)
    S_COL_VAT = FindHeaderColumn(ws, Array("THUE GTGT", "TIEN THUE GTGT", "TIEN THUE"), 15, foundRow): headerBottom = MaxLong(headerBottom, foundRow)

    RequireColumn S_COL_SERIES, ws.Name, "Ky hieu"
    RequireColumn S_COL_NUMBER, ws.Name, "So hoa don"
    RequireColumn S_COL_DATE, ws.Name, "Ngay hoa don"
    RequireColumn S_COL_TAX_ID, ws.Name, "Ma so thue"
    If requireFullWorkflow Then
        RequireColumn S_COL_MODEL, ws.Name, "Mau so"
        RequireColumn S_COL_PRETAX, ws.Name, "Doanh so chua thue"
        RequireColumn S_COL_RATE, ws.Name, "Thue suat"
        RequireColumn S_COL_VAT, ws.Name, "Thue GTGT"
    End If
    If S_COL_TOTAL_GOODS = 0 Then S_COL_TOTAL_GOODS = S_COL_PRETAX
    If S_COL_VENDOR = 0 Then S_COL_VENDOR = S_COL_TAX_ID - 2

    FIRST_SUMMARY_ROW = FirstDataRow(ws, S_COL_NUMBER, headerBottom + 1)
    If FIRST_SUMMARY_ROW = 0 Then FIRST_SUMMARY_ROW = headerBottom + 1
End Sub

Private Function FindHeaderColumn( _
    ByVal ws As Worksheet, _
    ByVal aliases As Variant, _
    ByVal maxHeaderRows As Long, _
    ByRef foundRow As Long) As Long

    Dim aliasValue As Variant, targetKey As String, cellKey As String
    Dim r As Long, c As Long, lastColumn As Long

    foundRow = 0
    lastColumn = ws.UsedRange.Column + ws.UsedRange.Columns.Count - 1
    If lastColumn > 250 Then lastColumn = 250

    For Each aliasValue In aliases
        targetKey = HeaderKey(CStr(aliasValue))
        For r = 1 To maxHeaderRows
            For c = 1 To lastColumn
                cellKey = HeaderKey(ws.Cells(r, c).Value2)
                If Len(cellKey) > 0 And StrComp(cellKey, targetKey, vbTextCompare) = 0 Then
                    FindHeaderColumn = c
                    foundRow = r
                    Exit Function
                End If
            Next c
        Next r
    Next aliasValue
End Function

' Neu cot W co dung tieu de Dien giai thi uu tien chinh cot W theo mau nguon.
Private Function FindHeaderRowInColumn( _
    ByVal ws As Worksheet, _
    ByVal sourceCol As Long, _
    ByVal aliases As Variant, _
    ByVal maxHeaderRows As Long) As Long

    Dim aliasValue As Variant, targetKey As String, cellKey As String
    Dim r As Long

    For Each aliasValue In aliases
        targetKey = HeaderKey(CStr(aliasValue))
        For r = 1 To maxHeaderRows
            cellKey = HeaderKey(ws.Cells(r, sourceCol).Value2)
            If Len(cellKey) > 0 And _
               StrComp(cellKey, targetKey, vbTextCompare) = 0 Then
                FindHeaderRowInColumn = r
                Exit Function
            End If
        Next r
    Next aliasValue
End Function

Private Function FirstDataRow( _
    ByVal ws As Worksheet, _
    ByVal keyColumn As Long, _
    ByVal startRow As Long) As Long

    Dim r As Long, lastRow As Long, valueText As String

    If startRow < 1 Then startRow = 1
    lastRow = LastUsedRowInColumn(ws, keyColumn)
    For r = startRow To lastRow
        valueText = Trim$(SafeText(ws.Cells(r, keyColumn).Value2))
        If Len(valueText) > 0 And Left$(valueText, 1) <> "(" Then
            FirstDataRow = r
            Exit Function
        End If
    Next r
End Function

Private Function FindNumberedFieldColumn( _
    ByVal ws As Worksheet, _
    ByVal fieldNumber As String, _
    ByVal maxHeaderRows As Long, _
    ByRef foundRow As Long) As Long

    Dim r As Long, c As Long, lastColumn As Long, cellText As String

    foundRow = 0
    lastColumn = ws.UsedRange.Column + ws.UsedRange.Columns.Count - 1
    If lastColumn > 250 Then lastColumn = 250

    For r = 1 To maxHeaderRows
        For c = 1 To lastColumn
            cellText = Trim$(SafeText(ws.Cells(r, c).Value2))
            cellText = Replace(cellText, "(", vbNullString)
            cellText = Replace(cellText, ")", vbNullString)
            cellText = Replace(cellText, " ", vbNullString)
            If StrComp(cellText, fieldNumber, vbTextCompare) = 0 Then
                FindNumberedFieldColumn = c
                foundRow = r
                Exit Function
            End If
        Next c
    Next r
End Function

Private Sub RequireColumn( _
    ByVal columnNumber As Long, _
    ByVal sheetName As String, _
    ByVal fieldName As String)

    If columnNumber = 0 Then
        Err.Raise vbObjectError + 1400, , _
            "Khong tim thay cot '" & fieldName & "' theo tieu de tren sheet '" & sheetName & "'."
    End If
End Sub

Private Function HeaderKey(ByVal value As Variant) As String
    Dim s As String, result As String, ch As String
    Dim i As Long

    s = UCase$(Trim$(SafeText(value)))
    ReplaceCodeList s, Array(192, 193, 194, 195, 224, 225, 226, 227, 258, 259, _
        7840, 7841, 7842, 7843, 7844, 7845, 7846, 7847, 7848, 7849, 7850, 7851, _
        7852, 7853, 7854, 7855, 7856, 7857, 7858, 7859, 7860, 7861, 7862, 7863), "A"
    ReplaceCodeList s, Array(200, 201, 202, 232, 233, 234, 7864, 7865, 7866, 7867, _
        7868, 7869, 7870, 7871, 7872, 7873, 7874, 7875, 7876, 7877, 7878, 7879), "E"
    ReplaceCodeList s, Array(204, 205, 296, 297, 236, 237, 7880, 7881, 7882, 7883), "I"
    ReplaceCodeList s, Array(210, 211, 212, 213, 214, 242, 243, 244, 245, 246, 416, 417, _
        7884, 7885, 7886, 7887, 7888, 7889, 7890, 7891, 7892, 7893, 7894, 7895, _
        7896, 7897, 7898, 7899, 7900, 7901, 7902, 7903, 7904, 7905, 7906, 7907), "O"
    ReplaceCodeList s, Array(217, 218, 360, 361, 220, 249, 250, 252, 431, 432, _
        7908, 7909, 7910, 7911, 7912, 7913, 7914, 7915, 7916, 7917, 7918, 7919, 7920, 7921), "U"
    ReplaceCodeList s, Array(221, 253, 7922, 7923, 7924, 7925, 7926, 7927, 7928, 7929), "Y"
    ReplaceCodeList s, Array(272, 273), "D"

    For i = 1 To Len(s)
        ch = Mid$(s, i, 1)
        If (ch >= "A" And ch <= "Z") Or (ch >= "0" And ch <= "9") Then
            result = result & ch
        ElseIf Len(result) > 0 And Right$(result, 1) <> " " Then
            result = result & " "
        End If
    Next i
    HeaderKey = Trim$(result)
End Function

Private Sub ReplaceCodeList( _
    ByRef textValue As String, _
    ByVal codeList As Variant, _
    ByVal replacement As String)

    Dim codeValue As Variant
    For Each codeValue In codeList
        textValue = Replace(textValue, ChrW(CLng(codeValue)), replacement)
    Next codeValue
End Sub

Private Function LastInvoiceRow(ByVal ws As Worksheet) As Long
    LastInvoiceRow = MaxLong( _
        LastUsedRowInColumn(ws, K_COL_MODEL), _
        LastUsedRowInColumn(ws, K_COL_SERIES), _
        LastUsedRowInColumn(ws, K_COL_NUMBER), _
        LastUsedRowInColumn(ws, K_COL_TAX_ID))
End Function

Private Function LastDetailRow(ByVal ws As Worksheet) As Long
    LastDetailRow = LastUsedRowInColumn(ws, D_COL_NUMBER)
End Function

Private Function LastSummaryRow(ByVal ws As Worksheet) As Long
    LastSummaryRow = LastUsedRowInColumn(ws, S_COL_NUMBER)
End Function

Private Function LastUsedRowInColumn(ByVal ws As Worksheet, ByVal columnNumber As Long) As Long
    If columnNumber <= 0 Then Exit Function
    LastUsedRowInColumn = ws.Cells(ws.rows.Count, columnNumber).End(xlUp).Row
End Function

Private Function MaxLong(ParamArray values() As Variant) As Long
    Dim i As Long, currentValue As Long

    For i = LBound(values) To UBound(values)
        currentValue = CLng(values(i))
        If currentValue > MaxLong Then MaxLong = currentValue
    Next i
End Function

Private Function GetTargetWorkbook() As Workbook
    Dim wb As Workbook

    On Error Resume Next
    Set wb = Application.ActiveWorkbook
    On Error GoTo 0

    If wb Is Nothing Then
        Err.Raise vbObjectError + 1300, , "Khong co workbook du lieu dang duoc kich hoat."
    End If
    If wb.IsAddin Then
        Err.Raise vbObjectError + 1301, , "Workbook dang kich hoat la Add-in, khong phai file du lieu."
    End If
    If StrComp(UCase$(wb.Name), "PERSONAL.XLSB", vbBinaryCompare) = 0 Then
        Err.Raise vbObjectError + 1302, , "Hay kich hoat file du lieu thang can xu ly, khong chon PERSONAL.XLSB."
    End If
    If wb.ReadOnly Then
        Err.Raise vbObjectError + 1303, , "Workbook dang Read-only. Hay Save As mot ban .xlsm truoc khi chay."
    End If

    Set GetTargetWorkbook = wb
End Function

Private Function FindWorksheet( _
    ByVal wb As Workbook, _
    ByVal firstName As String, _
    ByVal secondName As String) As Worksheet

    Dim ws As Worksheet
    Dim normalizedName As String
    Dim normalizedFirst As String, normalizedSecond As String

    normalizedFirst = NormalizeSheetName(firstName)
    normalizedSecond = NormalizeSheetName(secondName)

    For Each ws In wb.Worksheets
        normalizedName = NormalizeSheetName(ws.Name)
        If StrComp(normalizedName, normalizedFirst, vbTextCompare) = 0 Or _
           StrComp(normalizedName, normalizedSecond, vbTextCompare) = 0 Then
            Set FindWorksheet = ws
            Exit Function
        End If
    Next ws
End Function

Private Function NormalizeSheetName(ByVal sheetName As String) As String
    Dim s As String

    ' HeaderKey da xu ly day du cac dau tieng Viet va dau cau.
    s = HeaderKey(sheetName)
    s = Replace(s, " ", "")
    NormalizeSheetName = s
End Function

Private Function NewTextDictionary() As Object
    Dim dict As Object
    Set dict = CreateObject("Scripting.Dictionary")
    dict.CompareMode = vbTextCompare
    Set NewTextDictionary = dict
End Function

Private Sub AddLongToGroup( _
    ByVal dict As Object, _
    ByVal groupKey As String, _
    ByVal rowNumber As Long)

    Dim rows As Collection

    If dict.Exists(groupKey) Then
        Set rows = dict(groupKey)
    Else
        Set rows = New Collection
        dict.Add groupKey, rows
    End If
    rows.Add rowNumber
End Sub

Private Sub AddUniqueTextToGroup( _
    ByVal dict As Object, _
    ByVal groupKey As String, _
    ByVal textValue As String)

    Dim values As Collection
    Dim i As Long

    If dict.Exists(groupKey) Then
        Set values = dict(groupKey)
    Else
        Set values = New Collection
        dict.Add groupKey, values
    End If

    For i = 1 To values.Count
        If StrComp(CStr(values(i)), textValue, vbTextCompare) = 0 Then Exit Sub
    Next i
    values.Add textValue
End Sub

